<?php

namespace app\modules\orders\formModels;

use app\modules\orders\models\OrderDiscount;
use app\modules\orders\models\Orders;
use app\modules\orders\traits\CheckoutHelpers;
use app\validators\UuidValidator;
use yii\base\Model;

class CheckoutClearDiscounts extends Model
{
	use CheckoutHelpers;

	public $order_id;

	protected Orders|null $order;

	public function rules(): array
	{
		return [
			[['order_id'], 'required'],
			['order_id', UuidValidator::class],
			['order_id', 'validateOrder'],
		];
	}

	public function save(): false|array
	{
		if (!$this->validate()) {
			return false;
		}

		OrderDiscount::deleteAll(['order_id' => $this->order->order_id]);
		$this->order->reCalcOrderTotal();

		$publicOrder = Orders::find()
			->publicOrderScope()
			->where(['orders.order_id' => $this->order->order_id])
			->one()
		;

		return [
			'order' => $publicOrder,
			'total' => $this->order->makeOrderItems()->calcTotal()
		];
	}

	public function validateOrder()
	{
		$this->order = $this->findCheckoutOrder($this->order_id);
		if (!$this->order) {
			$this->addError('order_id', 'Order not found');
			return;
		}
	}
}
