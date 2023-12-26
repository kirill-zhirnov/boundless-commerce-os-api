<?php

namespace app\modules\user\controllers\customer;

use app\components\CustomerRestController;
use app\modules\user\formModels\CustomerUpdatePassForm;
use yii\web\ServerErrorHttpException;
use Yii;

class AuthController extends CustomerRestController
{
	public function actionUpdatePass()
	{
		$model = new CustomerUpdatePassForm();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');

		if ($model->save() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		return true;
	}
}
