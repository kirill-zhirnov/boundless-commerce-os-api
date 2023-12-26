<?php

namespace app\modules\orders\formModels;

use app\modules\orders\models\Orders;
use yii\base\Model;

class OrderCustomAttrs extends Model
{
	public $order_id;

	public $attrs;

	protected Orders|null $order;

	public function rules(): array
	{
		return [
			[['order_id', 'attrs'], 'required'],
			['order_id', 'validateOrder'],
			['attrs', 'validateAttrs'],
		];
	}

	public function save(): bool
	{
		if (!$this->validate()) {
			return false;
		}

		$this->order->orderProp->custom_attrs = $this->attrs;
		$this->order->orderProp->save(false);

		return true;
	}

	public function validateAttrs()
	{
		if (!is_array($this->attrs)) {
			$this->addError('attrs', 'Attrs should be an object');
			return;
		}

		foreach ($this->attrs as $key => $val) {
			if (is_numeric($key)) {
				$this->addError('attrs[' . $key . ']', 'Keys should not be numerical.');
				return;
			}
		}
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
		}
	}
}
