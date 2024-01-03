<?php

namespace app\modules\user\controllers\admin;

use app\components\filters\StrongToken;
use app\components\RestController;
use app\modules\user\formModels\AuthOrCreateForm;
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

	public function actionFindOrCreate()
	{
		$model = new AuthOrCreateForm();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');

		if ($model->process() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		$person = $model->getPerson();
		return [
			'person' => $person,
			'authToken' => $person->createAuthToken()
		];
	}
}
