<?php

namespace app\components\filters;

use Yii;
use yii\web\HttpException;
use yii\web\User;
use app\modules\user\models\User as UserModel;
use yii\base\ActionFilter;

class StrongToken extends ActionFilter
{
	public function beforeAction($action)
	{
		/** @var User $user */
		$user = Yii::$app->user;
		if ($user->isGuest) {
			throw new HttpException(403, 'Action not allowed for a guest user.');
		}

		/** @var UserModel $identity */
		$identity = $user->identity;
		if (!$identity->getToken()->hasManagementRights()) {
			throw new HttpException(403, 'Action allowed only for tokens with management rights.');
		}

		return true;
	}
}
