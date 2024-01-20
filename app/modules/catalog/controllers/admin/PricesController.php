<?php

namespace app\modules\catalog\controllers\admin;

use app\components\filters\StrongToken;
use app\components\RestController;
use app\modules\catalog\searchModels\PricesSearch;
use yii\helpers\ArrayHelper;
use Yii;

class PricesController extends RestController
{
	public function behaviors(): array
	{
		return ArrayHelper::merge(parent::behaviors(), [
			'strongToken' => [
				'class' => StrongToken::class
			]
		]);
	}

	public function actionIndex()
	{
		$model = new PricesSearch();
		return $model->search(Yii::$app->request->queryParams);
	}
}
