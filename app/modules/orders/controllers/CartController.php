<?php

namespace app\modules\orders\controllers;

use app\components\filters\HttpCustomerAuth;
use app\modules\orders\components\OrderItems;
use app\modules\orders\formModels\cart\InStockValidatorForm;
use app\modules\orders\formModels\CartAddCustomItem;
use app\modules\orders\formModels\CartBulkSetQtyForm;
use app\modules\orders\formModels\CartRmItemsForm;
use app\modules\orders\formModels\Item2CartForm;
use app\modules\orders\models\Basket;
use Yii;
use app\components\RestController;
use app\modules\orders\formModels\RetrieveCartForm;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;
use yii\web\ServerErrorHttpException;
use app\validators\UuidValidator;

class CartController extends RestController
{
	public function behaviors(): array
	{
		return ArrayHelper::merge(parent::behaviors(), [
			'customerAuth' => [
				'class' => HttpCustomerAuth::class,
				'isAuthOptional' => true
			]
		]);
	}

	protected function verbs()
	{
		return [
			'retrieve' => ['POST'],
			'add' => ['POST'],
			'total' => ['GET'],
			'items' => ['GET'],
			'set-qty' => ['POST'],
			'rm-items' => ['POST'],
			'add-custom-item' => ['POST'],
			'patch-set-qty' => ['PATCH'],
		];
	}

	public function actionRetrieve()
	{
		$model = new RetrieveCartForm();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');

		if ($model->save() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		return $model->getBasket();
	}

	public function actionAdd()
	{
		$model = new Item2CartForm();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');

		$result = $model->save();
		if ($result === false) {
			if (!$model->hasErrors()) {
				throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
			}

			return $model;
		}

		return $result;
	}

	public function actionTotal($id)
	{
		return $this->findBasket($id);
	}

	public function actionItems($id)
	{
		$basket = $this->findBasket($id);
		$orderItems = new OrderItems(basket: $basket);

		return [
			'cart' => $basket,
			'items' => $orderItems->getItems()
		];
	}

	//new way to set qty
	public function actionPatchSetQty($id)
	{
		$model = new CartBulkSetQtyForm();
		$model->load(
			ArrayHelper::merge(Yii::$app->getRequest()->getBodyParams(),
			['cart_id' => $id]
		), '');

		if ($model->save() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		return ['result' => true];
	}

	public function actionValidateStock($id)
	{
		$basket = $this->findBasket($id);

		$model = new InStockValidatorForm();
		$model->setCart($basket);

		if ($model->execValidation() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		return ['result' => true];
	}

	//legacy
	public function actionSetQty()
	{
		$model = new CartBulkSetQtyForm();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');

		if ($model->save() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		return ['result' => true];
	}

	public function actionRmItems()
	{
		$model = new CartRmItemsForm();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');

		if ($model->save() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		return ['result' => true];
	}

	public function actionAddCustomItem()
	{
		$model = new CartAddCustomItem();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');

		$result = $model->save();
		if ($result === false) {
			if (!$model->hasErrors()) {
				throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
			}

			return $model;
		}

		return $result;
	}

	protected function findBasket($id): Basket
	{
		$validator = new UuidValidator();
		if (!$validator->validate($id)) {
			throw new HttpException(422, 'Cart ID is not valid UUID.');
		}

		/** @var Basket $basket */
		$basket = Basket::find()->where(['public_id' => $id])->one();

		if (!$basket) {
			throw new HttpException(404);
		}

		if (!$basket->is_active) {
			throw new HttpException(422, 'The cart is inactive. Probably, an order has already been processed. Please retrieve another one.');
		}

		$basket->calcTotal();

		return $basket;
	}
}
