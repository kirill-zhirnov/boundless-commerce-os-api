<?php

namespace app\modules\catalog\controllers\admin;

use app\components\filters\StrongToken;
use app\components\RestController;
use app\modules\catalog\formModels\product\PatchProductForm;
use app\modules\catalog\formModels\product\ProductForm;
use app\modules\catalog\traits\ProductHelpers;
use yii\helpers\ArrayHelper;
use Yii;
use yii\web\ServerErrorHttpException;

class ManageProductController extends RestController
{
	use ProductHelpers;

	public function behaviors(): array
	{
		return ArrayHelper::merge(parent::behaviors(), [
			'strongToken' => [
				'class' => StrongToken::class
			]
		]);
	}

	public function actionCreate()
	{
		$model = new ProductForm();
		$model->setScenario('create');

		$model->load(Yii::$app->getRequest()->getBodyParams(), '');
		if ($model->save() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors())
			return $model;

		return $this->findProductWithLoader($model->getProduct()->product_id, false);
	}

	public function actionPatch($id)
	{
		$model = new PatchProductForm();
		$model
			->setProduct($this->findProductById($id))
		;

		$model->load(Yii::$app->getRequest()->getBodyParams(), '');
		if ($model->save() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors())
			return $model;

		return $this->findProductWithLoader($model->getProduct()->product_id, false);
	}
}
