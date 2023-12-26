<?php

namespace app\modules\catalog\controllers\admin;

use app\components\filters\StrongToken;
use app\components\RestController;
use app\modules\catalog\formModels\category\CategoryForm;
use app\modules\catalog\formModels\category\PatchCategoryForm;
use app\modules\catalog\traits\CategoryHelpers;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\ServerErrorHttpException;

class ManageCategoryController extends RestController
{
	use CategoryHelpers;

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
		$model = new CategoryForm();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');
		if ($model->save() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors())
			return $model;

		return $model->getCategory();
	}

	public function actionPatch($id)
	{
		$model = new PatchCategoryForm();
		$model->setCategory($this->findCategoryById($id));

		$model->load(Yii::$app->getRequest()->getBodyParams(), '');
		if ($model->save() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors())
			return $model;

		return $model->getCategory();
	}
}
