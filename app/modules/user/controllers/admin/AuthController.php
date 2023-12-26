<?php

namespace app\modules\user\controllers\admin;

use app\components\filters\StrongToken;
use app\components\RestController;
use app\modules\user\formModels\MakeMagickLinkForm;
use yii\helpers\ArrayHelper;
use yii\web\ServerErrorHttpException;
use Yii;

class AuthController extends RestController
{
	public function behaviors(): array
	{
		return ArrayHelper::merge(parent::behaviors(), [
			'strongToken' => [
				'class' => StrongToken::class
			]
		]);
	}

	public function actionMakeMagickLink()
	{
		$model = new MakeMagickLinkForm();
		$model
			->setInstance(Yii::$app->user->getIdentity()->getInstance())
			->load(Yii::$app->getRequest()->getBodyParams(), '')
		;

		$result = $model->makeLink();
		if ($result === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		return $result;
	}
}
