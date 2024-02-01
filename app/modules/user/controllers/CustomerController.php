<?php

namespace app\modules\user\controllers;

use app\components\RestController;
use app\modules\user\formModels\CustomerLoginForm;
use app\modules\user\formModels\RegisterCustomerForm;
use app\modules\user\models\Person;
use Yii;
use yii\web\ServerErrorHttpException;

class CustomerController extends RestController
{
	protected function verbs()
	{
		return [
			'login' => ['POST'],
			'register' => ['POST'],
		];
	}

	public function actionRegister()
	{
		$model = new RegisterCustomerForm();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');

		if ($model->save() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		/** @var Person $person */
		$person = Person::find()
			->publicPersonScope()
			->where(['person_id' => $model->getPerson()->person_id])
			->one()
		;

		return [
			'customer' => $person,
			'authToken' => $person->createAuthToken()
		];
	}

	public function actionLogin()
	{
		$model = new CustomerLoginForm();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');

		if ($model->validate() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		$person = $model->getPerson();
		$cart = $model->getCart();

		$out = [
			'customer' => $person,
			'authToken' => $person->createAuthToken(),
		];

		if ($cart) {
			$out['activeCart'] = $cart;
		}

		return $out;
	}
}
