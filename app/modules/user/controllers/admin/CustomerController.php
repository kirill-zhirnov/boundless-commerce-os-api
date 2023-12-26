<?php

namespace app\modules\user\controllers\admin;

use app\components\filters\StrongToken;
use app\components\RestController;
use app\modules\orders\searchModels\OrdersSearch;
use app\modules\user\formModels\MakeMagickLinkForm;
use app\modules\user\searchModels\CustomerSearch;
use yii\helpers\ArrayHelper;
use Yii;
use yii\web\ServerErrorHttpException;

class CustomerController extends RestController
{
	public function behaviors(): array
	{
		return ArrayHelper::merge(parent::behaviors(), [
			'strongToken' => [
				'class' => StrongToken::class
			]
		]);
	}

	protected function verbs(): array
	{
		return [
			'index' => ['GET']
		];
	}

	public function actionIndex()
	{
		$model = new CustomerSearch();
		return $model->search(Yii::$app->request->queryParams);
	}
}
