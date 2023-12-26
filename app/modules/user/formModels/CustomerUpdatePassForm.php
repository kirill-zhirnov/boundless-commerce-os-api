<?php

namespace app\modules\user\formModels;

use app\modules\user\models\PersonAuth;
use yii\base\Model;
use Yii;

class CustomerUpdatePassForm extends Model
{
	public $password;
	public $password_repeat;

	public function rules(): array
	{
		return [
			[['password', 'password_repeat'], 'required'],
			['password', 'compare', 'compareAttribute' => 'password_repeat'],
			[['password', 'password_repeat'], 'filter', 'filter' => 'strval'],
			[['password', 'password_repeat'], 'string', 'min' => '1'],
		];
	}

	public function save(): bool
	{
		if (!$this->validate()) {
			return false;
		}

		/** @var \yii\web\User $customerUser */
		$customerUser = Yii::$app->customerUser;

		$person = $customerUser->getIdentity()->getPerson();

		PersonAuth::setPass($person->person_id, $this->password);

		return true;
	}
}
