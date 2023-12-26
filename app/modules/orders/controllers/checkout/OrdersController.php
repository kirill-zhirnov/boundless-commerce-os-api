<?php

namespace app\modules\orders\controllers\checkout;

use app\components\filters\HttpCustomerAuth;
use app\components\RestController;
use app\modules\orders\formModels\checkout\OrderPlacerForm;
use app\modules\orders\formModels\CheckoutContactForm;
use app\modules\orders\formModels\orders\OrderAttrsForm;
use app\modules\orders\models\Orders;
use app\modules\orders\traits\CheckoutHelpers;
use yii\helpers\ArrayHelper;
use yii\web\ServerErrorHttpException;
use Yii;

class OrdersController extends RestController
{
	use CheckoutHelpers;

	public function behaviors(): array
	{
		return ArrayHelper::merge(parent::behaviors(), [
			'customerAuth' => [
				'class' => HttpCustomerAuth::class,
				'isAuthOptional' => true
			]
		]);
	}

	protected function verbs(): array
	{
		return [
			'place' => ['POST'],
			'update-attrs' => ['PATCH'],
			'contact' => ['POST'],
		];
	}

	public function actionPlace($orderId)
	{
		$order = $this->findCheckoutOrder($orderId);

		$model = new OrderPlacerForm();
		$model->setOrder($order);

		if ($model->save() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		return Orders::find()
			->orderPageScope()
			->where(['order_id' => $order->order_id])
			->one()
		;
	}

	public function actionUpdateAttrs($orderId)
	{
		$order = $this->findCheckoutOrder($orderId);

		$model = new OrderAttrsForm();
		$model->setScenario('customerForm');
		$model->setOrder($order);

		$model->load(Yii::$app->getRequest()->getBodyParams(), '');
		if ($model->save() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		return Orders::find()->orderPageScope()->where(['order_id' => $order->order_id])->one();
	}

	public function actionContact($orderId)
	{
//		$order = $this->findCheckoutOrder($orderId);
		$model = new CheckoutContactForm();
		$model->load(ArrayHelper::merge(
			Yii::$app->getRequest()->getBodyParams(),
			['order_id' => $orderId]
		), '');

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
