<?php

namespace app\modules\orders\formModels;

use app\modules\inventory\models\CustomItem;
use app\modules\inventory\models\InventoryItem;
use app\modules\orders\components\OrderItems;
use app\modules\orders\models\Basket;
use app\modules\orders\models\ItemPrice;
use app\validators\UuidValidator;
use yii\base\Model;

class CartAddCustomItem extends Model
{
	public $cart_id;

	public $title;

	public $price;

	public $qty;

	protected Basket $basket;

	public function rules(): array
	{
		return [
			[['cart_id', 'title', 'price', 'qty'], 'required'],
			[['cart_id'], UuidValidator::class],
			[['cart_id'], 'validateBasket'],
			[['price'],  'number', 'min' => 0],
			[['qty'],  'integer', 'min' => 1],
			[['title'], 'string', 'max' => 255],
			[['title'], 'trim'],
		];
	}

	public function save(): bool|array
	{
		if (!$this->validate()) {
			return false;
		}

		$customItem = new CustomItem();
		$customItem->attributes = [
			'title' => $this->title,
			'price' => $this->price
		];
		if (!$customItem->save()) {
			throw new \RuntimeException('Cannot save CustomItem: ' . print_r($customItem->getErrors(), 1));
		}

		$inventoryItem = InventoryItem::findOne(['custom_item_id' => $customItem->custom_item_id]);

		$orderItems = new OrderItems(basket: $this->basket);
		$orderItems->addItem($inventoryItem, intval($this->qty), ItemPrice::makeByFinalPrice(['value' => $this->price]));

		return [
			'customItem' => $customItem,
			'cartTotal' => $this->basket->calcTotal()
		];
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
