<?php

namespace app\modules\orders\formModels;

use app\modules\catalog\models\PointSale;
use app\modules\delivery\models\VwCountry;
use app\modules\inventory\models\VwInventoryItem;
use app\modules\orders\components\manipulatorForReserve\NotEnoughStockException;
use app\modules\orders\models\Orders;
use app\modules\orders\traits\CheckoutHelpers;
use app\modules\payment\components\PayPal;
use app\modules\payment\models\PaymentGateway;
use app\modules\payment\models\PaymentMethod;
use app\modules\system\models\Setting;
use app\modules\user\models\Person;
use app\modules\user\models\PersonAddress;
use app\validators\UuidValidator;
use yii\base\Model;
use Yii;
use yii\db\Connection;
use app\modules\orders\components\ManipulatorForReserve;
use yii\db\Expression;

class CheckoutPaymentForm extends Model
{
	use CheckoutHelpers;

	public $payment_method_id;

	public $order_id;

	public $payment_address_the_same;

	public $first_name;

	public $last_name;

	public $company;

	public $address_line_1;

	public $address_line_2;

	public $city;

	public $state;

	public $country_id;

	public $zip;

	protected Orders|null $order;

	protected PaymentMethod|null $paymentMethod;

	protected PersonAddress|null $personShippingAddress;

	public function rules(): array
	{
		$out = [
			[['order_id', 'payment_method_id'], 'required'],
			['order_id', UuidValidator::class],
			['order_id', 'validateOrder'],
			['payment_method_id', 'validatePaymentMethod'],
			['payment_address_the_same', 'in', 'range' => ['1']],

			//address related fields:
			[['first_name', 'last_name'], 'string', 'max' => 100, 'when' => [$this, 'shallValidateAddress']],
//			[['address_line_1', 'city', 'country_id', 'zip', 'last_name'], 'required', 'when' => [$this, 'shallValidateAddress']],
			[
				[
					'first_name', 'last_name', 'address_line_1',  'city', 'state',
					'zip'
				],
				'trim',
				'when' => [$this, 'shallValidateAddress']
			],
			[
				['country_id'],
				'exist',
				'targetClass' => VwCountry::class,
				'targetAttribute' => ['country_id' => 'country_id'],
				'when' => [$this, 'shallValidateAddress']
			],
		];

		$checkoutPageSettings = Setting::getCheckoutPage();
		if (in_array('first', $checkoutPageSettings['customerNameRequired'])) {
//			$out[] = ['first_name', 'required', 'when' => [$this, 'shallValidateAddress']];
		}

		if ($checkoutPageSettings['addressLine2'] == 'optional') {
			$out[] = ['address_line_2', 'trim', 'when' => [$this, 'shallValidateAddress']];
		} elseif ($checkoutPageSettings['addressLine2'] == 'required') {
//			$out[] = ['address_line_2', 'required', 'when' => [$this, 'shallValidateAddress']];
		}

		if ($checkoutPageSettings['companyName'] == 'optional') {
			$out[] = ['company', 'trim', 'when' => [$this, 'shallValidateAddress']];
		} elseif ($checkoutPageSettings['companyName'] == 'required') {
//			$out[] = ['company', 'required', 'when' => [$this, 'shallValidateAddress']];
		}

		return $out;
	}

	public function save(): false|array
	{
		if (!$this->validate()) {
			return false;
		}

		if (
			$this->shallValidateAddress()
			&& !empty($this->address_line_1) && !empty($this->city) && !empty($this->country_id) && !empty($this->zip) && !empty($this->last_name)
		) {
			$this->saveAddress();
		}

		/** @var Connection $db */
		$db = Yii::$app->get('instanceDb');
		$transaction = $db->beginTransaction();
		try {
			$this->saveOrderProps();
			$this->order->refresh();

			$manipulator = new ManipulatorForReserve();
			$manipulator->setOrder($this->order);
			$manipulator->createReserveByBasket($this->order->customer);

			$this->order->refresh();
			$this->order->reCalcOrderTotal();

			$transaction->commit();

			/** @var \app\components\InstancesQueue $queue */
			Yii::$app->queue
				->modelCreated(Orders::class, [$this->order->order_id], ['status_id' => $this->order->status_id], true, true)
			;

//			$this->order = Orders::find()->publicOrderScope()->where(['order_id' => $this->order->order_id])->one();

			return $this->getOutput();
		} catch (NotEnoughStockException $e) {
			$transaction->rollBack();

			$item = VwInventoryItem::findOne($e->itemId);
			$error = Yii::t('app', 'Not enough stock for "{title}", requested: {requested}, available: {available}', [
				'title' => $item->getTitle(),
				'requested' => $e->requestedQty,
				'available' => $item->available_qty
			]);
			$this->addError('error_id', $error);

			return false;
		} catch (\Exception $e) {
			$transaction->rollBack();

			throw $e;
		}
	}

	protected function saveAddress()
	{
		$address = PersonAddress::findOrCreateAddressByType($this->order->customer, PersonAddress::TYPE_BILLING);
		$address->attributes = [
			'first_name' => $this->first_name,
			'last_name' => $this->last_name,
			'address_line_1' => $this->address_line_1,
			'city' => $this->city,
			'state' => $this->state,
			'country_id' => $this->country_id,
			'zip' => $this->zip,
		];

		if ($this->isAttributeSafe('company')) {
			$address->company = $this->company;
		}

		if ($this->isAttributeSafe('address_line_2')) {
			$address->address_line_2 = $this->address_line_2;
		}

		$address->save(false);

		$personProfile = $this->order->customer->personProfile;
		if (empty($personProfile->first_name) && empty($personProfile->last_name)) {
			$personProfile->attributes = [
				'first_name' => $this->first_name,
				'last_name' => $this->last_name,
			];
			$personProfile->save(false);
		}

		$this->order->customer->checkDefaultAddressExists();
	}

	protected function getOutput(): array
	{
		switch ($this->paymentMethod->paymentGateway->alias) {
			case PaymentGateway::ALIAS_CASH_ON_DELIVERY:
				return [
					'redirectTo' => 'thank-you',
//					'order' => $this->order
				];

			case PaymentGateway::ALIAS_PAYPAL:
				try {
					$paypal = new PayPal($this->order);
					$out = $paypal->createPayPalOrder();

					if ($out) {
						return [
							'redirectTo' => 'url',
							'url' => $out['customerRedirectUrl']
						];
					}
				} catch (\Exception $e) {
					Yii::error('can generate pypal link:' . $e->getMessage());
				}

				return [
					'redirectTo' => 'thank-you',
					'error' => Yii::t('app', 'Cannot proceed with the payment, please try again.')
				];
			default:
				throw new \RuntimeException('Not implemented for gateway "' . $this->paymentMethod->paymentGateway->alias . '"');
		}
	}

	protected function saveOrderProps()
	{
		$statusId = Setting::getNewOrderStatusId();
		$this->order->point_id = PointSale::DEFAULT_POINT;
		$this->order->status_id = $statusId;
		$this->order->payment_method_id = $this->payment_method_id;
		$this->order->publishing_status = Orders::STATUS_PUBLISHED;
		$this->order->created_at = new Expression('now()');

		/** @var \yii\web\User $customerUser */
		$customerUser = Yii::$app->customerUser;
		if (!$customerUser->isGuest) {
			/** @var Person $loggedInCustomer */
			$loggedInCustomer = $customerUser->getIdentity()->getPerson();

			$this->order->created_by = $loggedInCustomer->person_id;
		}

		if (!$this->order->save(false)) {
			throw new \RuntimeException('Cannot save payment method:', print_r($this->order->getErrors(), 1));
		}
	}

	public function validatePaymentMethod()
	{
		if (!isset($this->order)) {
			return;
		}

		$serviceDelivery = $this->order->findServiceDelivery();
		$deliveryId = $serviceDelivery?->orderServiceDelivery->delivery_id ?: null;

		$this->paymentMethod = PaymentMethod::find()
			->publicScope()
			->findByDelivery($deliveryId)
			->andWhere(['payment_method.payment_method_id' => $this->payment_method_id])
			->one()
		;

		if (!$this->paymentMethod) {
			$this->addError('payment_method_id', 'Payment method is not valid for the order.');
			return;
		}
	}

	public function validateOrder()
	{
		$this->order = $this->findCheckoutOrder($this->order_id);
		if (!$this->order) {
			$this->addError('order_id', 'Order not found');
			return;
		}

		if ($this->order->needOrderShipping()) {
			$serviceDelivery = $this->order->findServiceDelivery();
			if (!$serviceDelivery || !$serviceDelivery->orderServiceDelivery?->delivery_id) {
				$this->addError('order_id', 'Shipping step is not filled.');
				return;
			}
		}

		if (!$this->order->customer_id) {
			$this->addError('order_id', 'Customer is not filled.');
			return;
		}

		$this->personShippingAddress = $this->order->customer?->findShippingAddress();
	}

	public function shallValidateAddress(): bool
	{
		if (strval($this->payment_address_the_same) !== '1' || !$this->personShippingAddress) {
			return true;
		}

		return false;
	}

	public function attributeLabels()
	{
		return [
			'country_id' => Yii::t('app', 'Country')
		];
	}
}
