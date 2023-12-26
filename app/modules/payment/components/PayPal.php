<?php

namespace app\modules\payment\components;

use app\modules\payment\models\PaymentGateway;
use app\modules\payment\models\PaymentRequest;
use app\modules\payment\models\PaymentTransaction;
use app\modules\system\models\Currency;
use app\modules\system\models\Setting;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\PayPalEnvironment;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use app\modules\orders\models\Orders;
use PayPalHttp\HttpException;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use Yii;
use yii\db\Connection;
use yii\db\Expression;

class PayPal
{
	const MODE_LIVE = 'live';
	const MODE_SANDBOX = 'sandbox';

	protected Orders|null $order = null;
	protected bool $needOrderShipping;
	protected Currency $currency;
	protected PaymentTransaction $paymentTransaction;
	protected $errorMessage;

	public function __construct(Orders|null $order = null)
	{
		if ($order) {
			$this->order = $order;
		}
	}

	public function approveAuthorizedOrder(string $paypalOrderId): bool
	{
		/** @var PaymentTransaction $paymentTransaction */
		$paymentTransaction = PaymentTransaction::find()
			->with(['order'])
			->joinWith('paymentMethod.paymentGateway')
			->where([
				'payment_transaction.external_id' => $paypalOrderId,
				'payment_gateway.alias' => PaymentGateway::ALIAS_PAYPAL
			])
			->one()
		;

		if (!$paymentTransaction) {
			throw new \RuntimeException('Cant find transaction by paypalOrderId: ' . $paypalOrderId);
		}

		$this->order = $paymentTransaction->order;

		if ($paymentTransaction->isCompleted()) {
			return true;
		}

		if (!$paymentTransaction->isAwaitingForCallback()) {
			return false;
		}

		$client = new PayPalHttpClient($this->makePayPalEnv());

		$infoRequest = new OrdersGetRequest($paymentTransaction->external_id);
		$orderInfoResponse = $client->execute($infoRequest);

		$markCompleted = false;
		if ($orderInfoResponse->result->status === 'COMPLETED') {
			$markCompleted = true;
		} elseif ($orderInfoResponse->result->status === 'APPROVED') {
			$captureRequest = new OrdersCaptureRequest($paymentTransaction->external_id);

			try {
				$captureResponse = $client->execute($captureRequest);
				if ($captureResponse->result->status === 'COMPLETED') {
					$markCompleted = true;
				}
			} catch (HttpException $e) {
				return false;
			}
		}

		if ($markCompleted) {
			$transaction = $this->getDb()->beginTransaction();
			$trxResult = null;

			try {
				$this->order->paid_at = new Expression('now()');
				$this->order->save(false);

				$paymentTransaction->status = PaymentTransaction::STATUS_COMPLETED;
				$paymentTransaction->save(false);

				$transaction->commit();
				$trxResult = true;
			} catch (\Exception $e) {
				$transaction->rollBack();
				Yii::error('save trx err:' . $e->getMessage());
			}

			if ($trxResult) {
				$this->order->refresh();

				/** @var \app\components\InstancesQueue $queue */
				$queue = Yii::$app->queue;
				$queue->modelUpdated(Orders::class, [$this->order->order_id], ['paid_at' => $this->order->paid_at]);

				return true;
			}
		}

		return false;
	}

	public function createPayPalOrder(): false|array
	{
		$this->validateIfAllowToPay();

		$this->currency = Setting::getCurrency();
		$client = new PayPalHttpClient($this->makePayPalEnv());
		$paypalRequest = $this->makeCreateRequest();

		$this->paymentTransaction = $this->createPaymentTransaction();
		$this->logPaymentRequest($paypalRequest->body);

		try {
			$paypalResponse = $client->execute($paypalRequest);
			$this->paymentTransaction->saveStatusTo(PaymentTransaction::STATUS_AWAITING_FOR_CALLBACK);

			$result = $paypalResponse->result;

			$this->paymentTransaction->attributes = [
				'status' => PaymentTransaction::STATUS_AWAITING_FOR_CALLBACK,
				'external_id' => $result->id
			];
			$this->paymentTransaction->save(false);

			$out = ['id' => $result->id];
			if (isset($result->links) && is_array($result->links)) {
				foreach ($result->links as $link) {
					if ($link->rel === 'approve' && $link->method === 'GET') {
						$out['customerRedirectUrl'] = $link->href;
					}
				}
			}

			if (!isset($out['customerRedirectUrl'])) {
				throw new \RuntimeException('Cant find customerRedirectUrl, payment_transaction_id:' . $this->paymentTransaction->payment_transaction_id);
			}

			return $out;
		} catch (HttpException $e) {
			$this->paymentTransaction->error = [
				'statusCode' => $e->statusCode,
				'message' => $e->getMessage()
			];
			$this->paymentTransaction->save(false);
			$this->errorMessage = $e->getMessage();
			return false;
		}
	}

	protected function validateIfAllowToPay()
	{
		if (!isset($this->order)) {
			throw new \RuntimeException('Order isnt specified');
		}

		if (!$this->order->paymentMethod?->paymentGateway?->isPayPal()) {
			throw new \RuntimeException('Payment gateway isnt paypal');
		}

		if ($this->order->paid_at) {
			throw new \RuntimeException('Order is already paid');
		}
	}

	protected function makeCreateRequest(): OrdersCreateRequest
	{
		$this->needOrderShipping = $this->order->needOrderShipping();
//		$orderItems = $this->order->makeOrderItems();
//		$totalCalculator = $orderItems->getTotalCalculator();

		$request = new OrdersCreateRequest();
		$request->prefer('return=representation');
		$request->body = [
			'intent' => 'CAPTURE',
			'purchase_units' => [
				$this->getPurchaseUnitByOrder()
			],
			'application_context' => [
				'cancel_url' => $this->order->paymentMethod->config['cancel_url'],
				'return_url' => $this->order->paymentMethod->config['return_url'],
				'shipping_preference' => $this->needOrderShipping ? 'SET_PROVIDED_ADDRESS' : 'NO_SHIPPING'
			]
		];

		return $request;
	}

	protected function getPurchaseUnitByOrder(): array
	{
		$out = [
			'reference_id' => $this->order->order_id,
			'amount' => [
				'value' => $this->order->total_price,
				'currency_code' => $this->currency->alias,
			],
			'description' => 'Payment for order #' . $this->order->order_id,
		];

		$shippingService = $this->order->findServiceDelivery();
		if ($this->needOrderShipping && $shippingService->orderServiceDelivery?->delivery_id && $this->order->customer) {
			$shippingAddress = $this->order->customer->findDefaultShippingAddress();

			$out['shipping'] = [
				'name' => [
					'full_name' => $this->order->customer->personProfile->getFullName()
				]
			];

			if ($shippingAddress && $shippingAddress->vwCountry) {
				$out['shipping']['address'] = [
					'address_line_1' => $shippingAddress->address_line_1,
					'address_line_2' => $shippingAddress->address_line_2,
					'admin_area_1' => $shippingAddress->state,
					'admin_area_2' => $shippingAddress->city,
					'postal_code' => $shippingAddress->zip,
					'country_code' => mb_strtoupper($shippingAddress->vwCountry->code)
				];
			}
		}

		return $out;
	}

	protected function makePayPalEnv(): PayPalEnvironment
	{
		$clientId = $this->order->paymentMethod->config['client_id'];
		$secret = $this->order->paymentMethod->config['secret'];

		switch ($this->order->paymentMethod->config['mode']) {
			case self::MODE_LIVE:
				return new ProductionEnvironment($clientId, $secret);

			case self::MODE_SANDBOX:
				return new SandboxEnvironment($clientId, $secret);

			default:
				throw new \RuntimeException('Unknown mode: "' . $this->order->paymentMethod->config['mode'] . '"');
		}
	}

	protected function createPaymentTransaction(array|null $data = null): PaymentTransaction
	{
		$paymentTransaction = new PaymentTransaction();
		$paymentTransaction->attributes = [
			'payment_method_id' => $this->order->payment_method_id,
			'status' => PaymentTransaction::STATUS_CREATED,
			'mark_up_amount' => $this->order->payment_mark_up,
			'total_amount' => $this->order->total_price,
			'currency_id' => $this->currency->currency_id,
			'order_id' => $this->order->order_id
		];
		if (!$paymentTransaction->save(false)) {
			throw new \RuntimeException('Cant save payment transaction: ' . print_r($paymentTransaction->getErrors(), 1));
		}

		return $paymentTransaction;
	}

	protected function logPaymentRequest(array $data): PaymentRequest
	{
		$request = new PaymentRequest();
		$request->payment_transaction_id = $this->paymentTransaction->payment_transaction_id;
		$request->request = $data;
		if (!$request->save(false)) {
			throw new \RuntimeException('cant save paymentRequest:' . print_r($request->getErrors(), 1));
		}

		return $request;
	}

	public function getDb(): Connection
	{
		return Yii::$app->get('instanceDb');
	}

	public function getOrder(): Orders|null
	{
		if (isset($this->order)) {
			return $this->order;
		}

		return null;
	}

	public function getErrorMessage()
	{
		return $this->errorMessage;
	}
}
