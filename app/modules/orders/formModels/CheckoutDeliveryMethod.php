<?php

namespace app\modules\orders\formModels;

use app\modules\delivery\models\Delivery;
use app\modules\orders\models\Orders;
use app\modules\orders\models\OrderService;
use app\modules\orders\traits\CheckoutHelpers;
use app\modules\user\models\PersonAddress;
use app\validators\UuidValidator;
use yii\base\Model;

class CheckoutDeliveryMethod extends Model
{
	use CheckoutHelpers;

	public $order_id;

	public $delivery_id;

	protected Orders|null $order;
	protected Delivery|null $delivery;

	public function rules(): array
	{
		return [
			[['order_id', 'delivery_id'], 'required'],
			['order_id', UuidValidator::class],
			['order_id', 'validateOrder'],
			['delivery_id', 'validateDelivery'],
		];
	}

	public function save(): bool
	{
		if (!$this->validate()) {
			return false;
		}

		$orderService = $this->order->findOrCreateServiceDelivery();

		$orderService
			->orderServiceDelivery
			->saveDeliveryId($this->delivery_id)
		;

		$itemPrice = $orderService->findOrCreateItemPrice();
		$price = (isset($this->delivery->shipping_config, $this->delivery->shipping_config['price'])) ? $this->delivery->shipping_config['price'] : 0;
		$itemPrice->saveSinglePrice($price);

		//trigger a trigger to recalculate total_price.
		OrderService::updateAll(['item_price_id' => $itemPrice->item_price_id], [
			'order_service_id' => $orderService->order_service_id
		]);

		$this->order->reCalcOrderTotal();

		return true;
	}

	public function validateDelivery()
	{
		$this->delivery = $this->findCheckoutDelivery($this->delivery_id);
		if (!$this->delivery) {
			$this->addError('delivery_id', 'Delivery method not found');
			return;
		}

		if ($this->hasErrors('order_id')) {
			return;
		}

		if (!$this->order->customer_id) {
			$this->addError('delivery_id', 'Customer should be specified for the order before submitting delivery method.');
			return;
		}

		if ($this->delivery->isRequiredShippingAddress()) {
			$customerAddress = PersonAddress::find()
				->where([
					'person_id' => $this->order->customer_id,
					'type' => PersonAddress::TYPE_SHIPPING
				])
				->one()
			;

			if (!$customerAddress) {
				$this->addError('delivery_id', "Customer doesn't have shipping address.");
				return;
			}
		}
	}

	public function validateOrder()
	{
		$this->order = $this->findCheckoutOrder($this->order_id);
		if (!$this->order) {
			$this->addError('order_id', 'Order not found');
			return;
		}
	}

	public function getOrder(): Orders|null
	{
		return $this->order;
	}
}
