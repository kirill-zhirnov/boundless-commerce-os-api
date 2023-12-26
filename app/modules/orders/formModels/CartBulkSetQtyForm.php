<?php

namespace app\modules\orders\formModels;

use app\modules\orders\components\OrderItems;
use app\modules\orders\models\Basket;
use app\modules\orders\validators\ItemsInStockValidator;
use app\validators\UuidValidator;
use yii\base\Model;

class CartBulkSetQtyForm extends Model
{
	public $cart_id;

	public $items;

	public $validate_stock;

	protected ?Basket $basket;

	public function rules(): array
	{
		return [
			[['validate_stock'], 'boolean'],
			[['cart_id', 'items'], 'required'],
			[['cart_id'], UuidValidator::class],
			[['cart_id'], 'validateBasket'],
			[['items'], 'validateItems'],
		];
	}

	public function save(): bool
	{
		if (!$this->validate()) {
			return false;
		}

		$orderItems = new OrderItems(basket: $this->basket);
		$orderItems->bulkSetQty($this->items);

		return true;
	}

	public function validateBasket()
	{
		$this->basket = Basket::find()
			->where([
				'public_id' => $this->cart_id,
			])
			->one()
		;

		if (!isset($this->basket)) {
			$this->addError('cart_id', 'Cart not found.');
			return;
		}

		if (!$this->basket->isActive()) {
			$this->addError('cart_id', 'Cart is inactive.');
			return;
		}
	}

	public function validateItems()
	{
		if ($this->hasErrors('cart_id')) {
			return;
		}

		if (!is_array($this->items)) {
			$this->addError('items', 'Items is not an array');
			return;
		}

		$desiredQty = [];
		foreach ($this->items as $key => &$item) {
			if (!isset($item['item_id']) || !is_numeric($item['item_id'])) {
				$this->addError('items[' . $key .']', 'item_id is not a number');
				return;
			}

			if (!isset($item['qty']) || !is_numeric($item['qty'])) {
				$this->addError('items[' . $key .']', 'qty is not a number');
				return;
			}

			$item['qty'] = intval($item['qty']);
			if ($item['qty'] < 0 || $item['qty'] > 32767) {
				$this->addError('items[' . $key .']', 'qty is out of range');
				return;
			}

			$desiredQty[$item['item_id']] = $item['qty'];
		}

		if ($this->validate_stock && isset($this->basket)) {

			$validator = new ItemsInStockValidator([
				'basket' => $this->basket,
				'desiredQty' => $desiredQty
			]);
			$validator->validateAttribute($this, 'items');
		}
	}
}
