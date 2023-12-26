<?php

namespace app\modules\orders\controllers\checkout;

use app\components\filters\HttpCustomerAuth;
use app\components\RestController;
use app\modules\delivery\models\Delivery;
use app\modules\delivery\models\VwCountry;
use app\modules\orders\formModels\CheckoutDeliveryMethod;
use app\modules\orders\formModels\CheckoutShippingAddress;
use app\modules\orders\models\Orders;
use app\modules\orders\traits\CheckoutHelpers;
use app\modules\user\models\Person;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;
use yii\web\ServerErrorHttpException;
use yii\web\User;
use Yii;

class ShippingController extends RestController
{
	use CheckoutHelpers;

	protected function verbs(): array
	{
		return [
			'page' => ['GET'],
			'address' => ['POST'],
			'delivery-method' => ['POST'],
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

		$billingAddress = null;
		$shippingAddress = null;
		$person = null;

		/** @var User $customerUser */
		$customerUser = Yii::$app->customerUser;
		if (!$customerUser->isGuest) {
			/** @var Person $person */
			$person = $customerUser->getIdentity()->getPerson();
			$shippingAddress = $person->findShippingAddress();
			$billingAddress = $person->findBillingAddress();
		} elseif ($order->customer_id) {
			/** @var Person $person */
			$person = Person::find()
				->publicPersonScope()
				->where(['person_id' => $order->customer_id])
				->one()
			;
			$shippingAddress = $person?->findShippingAddress();
			$billingAddress = $person?->findBillingAddress();
		}

		return [
			'shippingAddress' => $shippingAddress,
			'billingAddress' => $billingAddress,
			'orderServiceDelivery' => $order->findServiceDelivery(),
			'person' => $person,
			'options' => [
				'delivery' => Delivery::find()->publicOptions()->all(),
				'country' => VwCountry::find()->publicOptions()->all()
			],
		];
	}

	public function actionAddress()
	{
		$model = new CheckoutShippingAddress();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');

		if ($model->save() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		$order = Orders::find()
			->publicOrderScope()
			->where(['order_id' => $model->getOrder()->order_id])
			->one()
		;

		return ['order' => $order];
	}

	public function actionDeliveryMethod()
	{
		$model = new CheckoutDeliveryMethod();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');

		if ($model->save() === false && !$model->hasErrors()) {
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
		$totalCalculator = $order->makeOrderItems()->getTotalCalculator();

		return ['order' => $order, 'total' => $totalCalculator->calcTotal()];
	}
}
