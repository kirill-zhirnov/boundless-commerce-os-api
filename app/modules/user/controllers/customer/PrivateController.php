<?php

namespace app\modules\user\controllers\customer;

use app\components\CustomerRestController;
use Yii;

class PrivateController extends CustomerRestController
{
	public function actionWhoAmI()
	{
		/** @var \yii\web\User $customerUser */
		$customerUser = Yii::$app->customerUser;

		return $customerUser->getIdentity()->getPerson();
	}
}
