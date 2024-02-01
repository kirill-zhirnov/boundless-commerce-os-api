<?php

namespace app\modules\orders\formModels;

use app\modules\orders\models\Basket;
use Yii;
use yii\web\User;

class RetrieveCartForm extends \yii\base\Model
{
	protected ?Basket $basket;

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

		/** @var User $customerUser */
		$customerUser = Yii::$app->customerUser;
		if ($customerUser->isGuest) {
			$this->basket = new Basket();
			$this->basket->save(false);
			$this->basket->refresh();
		} else {
			$person = $customerUser->getIdentity()->getPerson();
			$this->basket = Basket::findOrCreatePersonBasket($person);
		}

		$this->basket->calcTotal();

		return true;
	}

	public function getBasket(): ?Basket
	{
		return $this->basket;
	}
}
