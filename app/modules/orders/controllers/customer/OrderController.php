<?php

namespace app\modules\orders\controllers\customer;

use app\components\RestController;
use app\modules\orders\formModels\OrderAddressesForm;
use app\modules\orders\formModels\OrderCustomAttrs;
use app\modules\orders\formModels\OrderPaymentLinkForm;
use app\modules\orders\models\Orders;
use yii\web\HttpException;
use yii\web\ServerErrorHttpException;
use Yii;

class OrderController extends RestController
{
	protected function verbs(): array
	{
		return [
			'get-order' => ['GET'],
			'set-custom-attrs' => ['POST'],
			'make-payment-link' => ['POST'],
		];
	}

	public function actionGetOrder($id)
	{
		/** @var Orders $order */
		$order = Orders::find()
			->orderPageScope()
			->byPublicId($id)
			->one()
		;

		if (!$order) {
			throw new HttpException(404, 'Order not found.');
		}

		$items = $order->makeOrderItems();
		$order->setFieldItems($items->getItems());

		$order->setShallExportInternalId(true);

		return $order;
	}

	public function actionSetCustomAttrs()
	{
		$model = new OrderCustomAttrs();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');

		if ($model->save() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		return true;
	}

	public function actionMakePaymentLink()
	{
		$model = new OrderPaymentLinkForm();
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

	public function actionSetAddresses()
	{
		$model = new OrderAddressesForm();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');

		$result = $model->save();
		if ($result === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		/** @var Orders $order */
		$order = Orders::find()
			->publicOrderScope()
			->where(['order_id' => $model->getOrder()->order_id])
			->one()
		;
		$order->reCalcOrderTotal();
		$totalCalculator = $order->makeOrderItems()->getTotalCalculator();

		return [
			'order' => $order,
			'total' => $totalCalculator->calcTotal()
		];
	}
}
