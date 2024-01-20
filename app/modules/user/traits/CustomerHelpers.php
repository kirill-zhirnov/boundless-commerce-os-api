<?php

namespace app\modules\user\traits;

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
}
