<?php

namespace app\modules\orders\validators;

use app\modules\orders\models\Orders;
use app\modules\system\models\Setting;
use yii\validators\Validator;
use Yii;

class CustomerLoginStatusValidator extends Validator
{
	public function validateAttribute($model, $attribute)
	{
		/** @var \yii\web\User $customerUser */
		$customerUser = Yii::$app->customerUser;

		$accountPolicy = Setting::getCheckoutPage()['accountPolicy'];

		if ($accountPolicy === Orders::CHECKOUT_ACCOUNT_POLICY_LOGIN_REQUIRED && $customerUser->isGuest) {
			$this->addError($model, $attribute, Yii::t('app', 'User should be logged in to be able process the action.'));
			return;
		}
	}
}
