<?php

namespace app\modules\catalog\controllers\admin;

use app\components\filters\StrongToken;
use app\components\RestController;
use app\modules\catalog\formModels\characteristic\CharacteristicForm;
use app\modules\catalog\formModels\characteristic\PatchCharacteristicForm;
use app\modules\catalog\traits\CharacteristicHelpers;
use yii\helpers\ArrayHelper;
use yii\web\ServerErrorHttpException;
use Yii;

class ManageCharacteristicController extends RestController
{
	use CharacteristicHelpers;

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
		$model = new CharacteristicForm();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');
		if ($model->save() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors())
			return $model;

		return $this->findCharacteristicById($model->getCharacteristic()->characteristic_id);
	}

	public function actionPatch($id)
	{
		$model = new PatchCharacteristicForm();
		$model->setCharacteristic($this->findCharacteristicById($id));
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');
		if ($model->save() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors())
			return $model;

		return $this->findCharacteristicById($model->getCharacteristic()->characteristic_id);
	}
}
