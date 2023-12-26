<?php

namespace app\modules\orders\formModels;

use app\modules\orders\components\OrderItems;
use app\modules\orders\models\Basket;
use yii\base\Model;
use app\validators\UuidValidator;

class CartRmItemsForm extends Model
{
	public $cart_id;

	public $items;

	protected Basket $basket;

	public function rules(): array
	{
		return [
			[['cart_id', 'items'], 'required'],
			[['cart_id'], UuidValidator::class],
			[['cart_id'], 'validateBasket'],
			[['items'],  'each', 'rule' => ['integer']],
		];
	}

	public function save(): bool
	{
		if (!$this->validate()) {
			return false;
		}

		$orderItems = new OrderItems(basket: $this->basket);
		$orderItems->rmItems($this->items);

		return true;
	}

	public function validateBasket()
	{
		$this->basket = Basket::find()
			->where([
				'public_id' => $this->cart_id,
				'is_active' => true
			])
			->one()
		;

		if (!$this->basket) {
			$this->addError('cart_id', 'Cart not found');
			return;
		}
	}
}
