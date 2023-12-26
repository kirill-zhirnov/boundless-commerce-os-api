<?php

namespace app\controllers;

use app\components\actions\JsonErrorAction;
use app\components\actions\OptionsAction;
use app\components\RestController;
//use yii\web\Controller;
use Yii;

//use yii\rest\Controller;

class SiteController extends RestController
{
	public function behaviors(): array
	{
		$behaviours = parent::behaviors();

		if (isset($behaviours['authenticator'])) {
			unset($behaviours['authenticator']);
		}

		return $behaviours;
	}

	public function actions()
	{
		return [
			'error' => [
				'class' => JsonErrorAction::class,
			],
			'options' => [
				'class' => OptionsAction::class,
			],
		];
	}
}
