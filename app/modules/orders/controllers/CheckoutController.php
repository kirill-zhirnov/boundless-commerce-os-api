<?php

namespace app\modules\orders\controllers;

use app\components\filters\HttpCustomerAuth;
use app\components\RestController;
use app\modules\orders\formModels\CheckoutClearDiscounts;
use app\modules\orders\formModels\CheckoutContactForm;
use app\modules\orders\formModels\CheckoutDiscountCode;
use app\modules\orders\formModels\CheckoutInit;
use yii\helpers\ArrayHelper;
use yii\web\ServerErrorHttpException;
use Yii;

class CheckoutController extends RestController
{
	protected function verbs()
	{
		return [
			'init' => ['POST'],
			'contact' => ['POST'],
			'discount-code' => ['POST'],
			'clear-discounts' => ['POST'],
			'delete-discounts' => ['DELETE'],
		];
	}

	public function behaviors(): array
	{
		return ArrayHelper::merge(parent::behaviors(), [
			'customerAuth' => [
				'class' => HttpCustomerAuth::class,
				'isAuthOptional' => true
			]
		]);
	}

	public function actionInit()
	{
		$model = new CheckoutInit();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');

		$result = $model->init();
		if ($result === false) {
			if (!$model->hasErrors()) {
				throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
			}

			return $model;
		}

		return $result;
	}

	public function actionContact()
	{
		$model = new CheckoutContactForm();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');

		$result = $model->save();
		if ($result === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		return $result;
	}

	public function actionDiscountCode()
	{
		$model = new CheckoutDiscountCode();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');

		$out = $model->save();
		if ($out === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		return $out;
	}

	//legacy
	public function actionClearDiscounts()
	{
		$model = new CheckoutClearDiscounts();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');

		$result = $model->save();
		if ($result === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		return $result;
	}

	public function actionDeleteDiscounts($orderId)
	{
		$model = new CheckoutClearDiscounts();
		$model->load(['order_id' => $orderId], '');

		$result = $model->save();
		if ($result === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		return $result;
	}
}
