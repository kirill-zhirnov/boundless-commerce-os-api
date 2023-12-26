<?php

namespace app\modules\orders\controllers;

use app\components\RestController;
use app\modules\orders\models\OrderStatus;

class StatusController extends RestController
{
	public function actionIndex()
	{
		return OrderStatus::find()
			->with('statusText')
			->where(['deleted_at' => null])
			->orderBy(['sort' => SORT_ASC])
			->all()
		;
	}
}
