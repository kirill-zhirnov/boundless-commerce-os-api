<?php

namespace app\modules\user\formModels;

use app\modules\system\models\Site;
use app\modules\user\models\Person;
use yii\base\Model;
use Yii;

class CustomerLoginForm extends Model
{
	public $email;
	public $password;

	protected Person|null $person;

	public function rules(): array
	{
		return [
			[['email', 'password'], 'required'],
			['email', 'email'],
			['email', 'tryToLogin']
		];
	}

	public function tryToLogin()
	{
		$this->person = Person::find()
			->innerJoinWith('personAuth')
			->with(['personProfile', 'personAddresses'])
			->where('
				person.site_id = :site
				and person.email = :email
				and person.registered_at is not null
				and person_auth.pass = crypt(:pass, pass)
			', [
				'site' => Site::DEFAULT_SITE,
				'email' => $this->email,
				'pass' => $this->password
			])
			->one()
		;

		if (!$this->person) {
			$this->addError('password', Yii::t('app', 'Incorrect email or password'));
			return;
		}
	}

	public function getPerson()
	{
		return $this->person;
	}
}
