<?php

namespace app\modules\orders\controllers\admin;

use app\components\filters\StrongToken;
use app\components\RestController;
use app\modules\orders\formModels\orders\AdminOrderAttrsForm;
use app\modules\orders\models\AdminOrders;
use app\modules\orders\models\Orders;
use app\modules\orders\traits\AdminOrdersHelpers;
use yii\helpers\ArrayHelper;
use yii\web\ServerErrorHttpException;
use Yii;

class OrderController extends RestController
{
	use AdminOrdersHelpers;

	public function behaviors(): array
	{
		return ArrayHelper::merge(parent::behaviors(), [
			'strongToken' => [
				'class' => StrongToken::class
			]
		]);
	}

	protected function verbs(): array
	{
		return [
			'patch-order' => ['PATCH'],
		];
	}

	public function actionPatchOrder($orderId)
	{
		$order = $this->findAdminOrder($orderId);

		$model = new AdminOrderAttrsForm();
		$model->setScenario('adminForm');
		$model->setOrder($order);

		$model->load(Yii::$app->getRequest()->getBodyParams(), '');
		if ($model->save() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		return AdminOrders::find()
			->orderPageScope()
			->where(['order_id' => $order->order_id])
			->one()
		;
	}

//	public function actionUpdate($id)
//	{
//		$order = $this->findAdminOrder($id);
//
//		$model = new AdminOrderUpdate();
//		$model->setOrder($order);
//		$model->load(Yii::$app->getRequest()->getBodyParams(), '');
//
//		if ($model->save() === false && !$model->hasErrors()) {
//			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
//		}
//
//		if ($model->hasErrors()) {
//			return $model;
//		}
//
//		return $this->findAdminOrder($id, true);
//	}
}
