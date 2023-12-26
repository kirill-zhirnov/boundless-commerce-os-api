<?php

namespace app\modules\system\controllers;

use app\components\RestController;
use app\modules\delivery\models\VwCountry;

class CountryController extends RestController
{
	public function actionIndex()
	{
		return VwCountry::find()->publicOptions()->all();
	}
}
