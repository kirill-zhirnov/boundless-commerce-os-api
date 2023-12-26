<?php

namespace app\modules\orders\formModels\cart;

use app\modules\orders\components\OrderItems;
use app\modules\orders\models\Basket;
use app\modules\orders\validators\ItemsInStockValidator;
use app\modules\orders\validators\MinOrderAmountValidator;
use yii\base\Model;

class InStockValidatorForm extends Model
{
	public $items;

	protected ?Basket $cart;

	public function rules(): array
	{
		return [
			[
				'items',
				ItemsInStockValidator::class,
				'skipOnEmpty' => false,
				'basket' => $this->cart
			],
			[
				'items',
				MinOrderAmountValidator::class,
				'skipOnEmpty' => false,
				'basket' => $this->cart
			]
		];
	}

	public function execValidation(): bool
	{
		if (!isset($this->cart)) {
			throw new \RuntimeException('Cart should be set prior calling the method.');
		}

		if (!$this->validate()) {
			return false;
		}

		return true;
	}

	public function validateItems()
	{
		$orderItems = new OrderItems(basket: $this->cart);
		$orderItems->getItems();
	}

	public function getCart(): ?Basket
	{
		return $this->cart;
	}


	public function setCart(?Basket $cart): self
	{
		$this->cart = $cart;
		return $this;
	}
}
