<?php

namespace app\modules\orders\formModels;

use app\modules\orders\models\Orders;
use app\modules\payment\components\PayPal;
use yii\base\Model;

class OrderPaymentLinkForm extends Model
{
	public $order_id;

	protected Orders|null $order;

	public function rules(): array
	{
		return [
			['order_id', 'required'],
			['order_id', 'validateOrder'],
		];
	}

	public function save(): false|array
	{
		if (!$this->validate()) {
			return false;
		}

		try {
			$paypal = new PayPal($this->order);
			$out = $paypal->createPayPalOrder();

			if ($out) {
				return ['url' => $out['customerRedirectUrl']];
			} else {
				$this->addError('order_id', 'Can not create payment transactions: ' . $paypal->getErrorMessage());
			}
		} catch (\Exception $e) {
			$this->addError('order_id', $e->getMessage());
		}

		return false;
	}

	public function validateOrder()
	{
		/** @var Orders order */
		$this->order = Orders::find()
			->byPublicId($this->order_id)
			->one()
		;

		if (!$this->order) {
			$this->addError('order_id', 'Order not found');
			return;
		}

		if ($this->order->paid_at) {
			$this->addError('order_id', 'The order has already been paid.');
			return;
		}
	}
}
