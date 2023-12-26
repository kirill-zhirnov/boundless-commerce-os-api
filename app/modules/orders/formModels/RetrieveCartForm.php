<?php

namespace app\modules\orders\formModels;

use app\modules\orders\models\Basket;

class RetrieveCartForm extends \yii\base\Model
{
	public $person;

	protected Basket $basket;

	public function rules(): array
	{
		return [
//			validate, that person has correct uuid
//			['person', 'safe']
		];
	}

	public function save(): bool
	{
		if (!$this->validate()) {
			return false;
		}

		//если юзер передан, то нужно переделать на select * from basket_get(:person) -
		//получать активную корзину текущего юзера
		$this->basket = new Basket();
		if (!$this->basket->save()) {
			throw new \RuntimeException('Cannot save basket');
		}
		$this->basket->refresh();
		$this->basket->calcTotal();

		return true;
	}

	public function getBasket(): Basket
	{
		return $this->basket;
	}
}
