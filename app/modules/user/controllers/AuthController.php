<?php

namespace app\modules\user\controllers;

use app\components\RestController;
use app\modules\user\formModels\RestorePassEmailForm;
use app\modules\user\formModels\ValidateMagickLinkForm;
use yii\web\ServerErrorHttpException;
use Yii;

class AuthController extends RestController
{
	protected function verbs()
	{
		return [
			'validate-magick-link' => ['POST'],
		];
	}

	public function actionValidateMagickLink()
	{
		$model = new ValidateMagickLinkForm();
		$model
			->setInstance(Yii::$app->user->getIdentity()->getInstance())
			->load(Yii::$app->getRequest()->getBodyParams(), '')
		;

		$result = $model->process();
		if ($result === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		return $result;
	}

	public function actionMailRestoreLink()
	{
		$model = new RestorePassEmailForm();
		$model
			->setInstance(Yii::$app->user->getIdentity()->getInstance())
			->load(Yii::$app->getRequest()->getBodyParams(), '')
		;

		$result = $model->sendLink();
		if ($result === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		return $result;
	}
}
