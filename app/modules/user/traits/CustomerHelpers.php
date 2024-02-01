<?php

namespace app\modules\user\traits;

use app\modules\orders\models\Basket;
use app\modules\user\models\Person;
use yii\web\NotFoundHttpException;

trait CustomerHelpers
{
	public function findCustomerByPublicId(string $publicId): Person
	{
		/** @var Person $person */
		$person = Person::find()
			->publicPersonScope()
			->where([
				'public_id' => $publicId,
				'status' => Person::STATUS_PUBLISHED,
				'deleted_at' => null
			])
			->one();
		;

		if (!$person) {
			throw new NotFoundHttpException('Customer not found');
		}

		return $person;
	}

	public function processCustomerCartOnLogin(Person $customer, string $cartId): void
	{
		$existingActiveCart = Basket::find()
			->where(['person_id' => $customer->person_id, 'is_active' => true])
			->one()
		;

		/** @var Basket $guestCart */
		$guestCart = Basket::find()
			->where(['public_id' => $cartId, 'is_active' => true, 'person_id' => null])
			->one()
		;

		if (!$guestCart) {
			return;
		}

		if (!$existingActiveCart) {
			Basket::updateAll(['person_id' => $customer->person_id], [
				'basket_id' => $guestCart->basket_id,
				'person_id' => null
			]);
			return;
		}

		//if both cart exist - merge baskets - copy basket items from guest to existing Active
		$guestCart->copyItemsTo($existingActiveCart);
		$guestCart->makeInactive();
	}
}
