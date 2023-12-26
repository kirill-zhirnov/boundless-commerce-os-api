<?php

namespace app\modules\orders\controllers\checkout;

use app\components\filters\HttpCustomerAuth;
use app\components\RestController;
use app\modules\delivery\models\VwCountry;
use app\modules\orders\formModels\CheckoutPaymentForm;
use app\modules\orders\formModels\CheckoutPaypalCaptureForm;
use app\modules\orders\traits\CheckoutHelpers;
use app\modules\payment\models\PaymentMethod;
use app\modules\user\models\PersonAddress;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;
use yii\web\ServerErrorHttpException;
use Yii;

class PaymentController extends RestController
{
	use CheckoutHelpers;

	protected function verbs(): array
	{
		return [
			'page' => ['GET'],
			'set' => ['POST'],
			'paypal-capture' => ['POST'],
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

	public function actionPage($id)
	{
		$order = $this->findCheckoutOrder($id);
		if (!$order) {
			throw new HttpException(404, 'Order not found.');
		}

		$serviceDelivery = $order->findServiceDelivery();
		$deliveryId = $serviceDelivery?->orderServiceDelivery->delivery_id ?: null;

		/** @var PaymentMethod[] $paymentMethods */
		$paymentMethods = PaymentMethod::find()
			->publicScope()
			->findByDelivery($deliveryId)
			->onlyConfigured()
			->all()
		;

		$shippingAddress = $order->customer?->findShippingAddress();
		$billingAddress = $order->customer?->findBillingAddress();

//		$requiredBillingAddress = $shippingAddress ? false : true;

		return [
			'paymentMethods' => $paymentMethods,
			'billingAddress' => $billingAddress,
//			'requiredBillingAddress' => $requiredBillingAddress,
			'countries' => VwCountry::find()->publicOptions()->all()
		];
	}

	public function actionSet()
	{
		$model = new CheckoutPaymentForm();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');

		$result = $model->save();
		if ($result === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($result === false) {
			return $model;
		}

		return $result;
	}

	public function actionPaypalCapture()
	{
		$model = new CheckoutPaypalCaptureForm();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');

		$result = $model->save();
		if ($result === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($result === false) {
			return $model;
		}

		return $result;
	}
}
