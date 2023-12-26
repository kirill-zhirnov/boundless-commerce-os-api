<?php

namespace app\modules\user\formModels;

use app\components\InstancesQueue;
use app\modules\system\models\Setting;
use app\modules\system\models\Site;
use app\modules\user\models\PersonAuth;
use app\modules\user\models\PersonProfile;
use app\modules\user\validators\CustomAttrsValidator;
use app\modules\user\validators\PhoneValidator;
use yii\base\Model;
use app\modules\user\models\Person;
use yii\db\Expression;
use yii\db\Query;
use Yii;

class RegisterCustomerForm extends Model
{
	public $email;
	public $password;
	public $re_password;
	public $first_name;
	public $last_name;
	public $phone;
	public $receive_marketing_info;
	public $custom_attrs;
	public $private_comment;
	public $send_welcome_email;
	public $login_url;

	protected Person|null $person = null;

	public function rules(): array
	{
		$out = [
			['email', 'email'],
			['email', 'filter', 'filter' => 'strtolower'],
			[['email', 'password', 're_password'], 'required'],
			[
				'email',
				'unique',
				'targetClass' => Person::class,
				'targetAttribute' => 'email',
				'filter' => function(Query $query) {
					$query->andWhere('registered_at is not null');
				}
			],
			['password', 'compare', 'compareAttribute' => 're_password'],
			[['first_name', 'last_name', 'private_comment'], 'string', 'max' => 1000],
			[['first_name', 'last_name', 'private_comment'], 'trim'],
			['phone', PhoneValidator::class],
			[['receive_marketing_info'], 'boolean'],
			['custom_attrs', CustomAttrsValidator::class],
			['send_welcome_email', 'boolean'],
			['login_url', 'safe'],
//			['login_url', 'url'],
		];

//		$customerNameRequired = Setting::getCheckoutPage()['customerNameRequired'];
//		if (in_array('first', $customerNameRequired)) {
//			$out[] = ['first_name', 'required'];
//		}

		return $out;
	}

	public function save(): bool
	{
		if (!$this->validate()) {
			return false;
		}

		$this->person = new Person();
		$this->person->attributes = [
			'site_id' => Site::DEFAULT_SITE,
			'email' => $this->email,
			'registered_at' => new Expression('now()'),
		];
		$this->person->save(false);

		$this->person->setClientRoles();

		PersonProfile::updateAll([
			'first_name' => $this->first_name === '' ? null : $this->first_name,
			'last_name' => $this->last_name === '' ? null : $this->last_name,
			'phone' => $this->phone === '' ? null : $this->phone,
			'receive_marketing_info' => (bool) $this->receive_marketing_info,
			'comment' => $this->private_comment === '' ? null : $this->private_comment,
			'custom_attrs' => empty($this->custom_attrs) ? null : $this->custom_attrs
		], ['person_id' => $this->person->person_id]);

		PersonAuth::setPass($this->person->person_id, $this->password);

		if ($this->send_welcome_email) {
			/** @var InstancesQueue $queue */
			$queue = Yii::$app->queue;
			$queue->sendMail(InstancesQueue::MAIL_WELCOME_EMAIL, [
				'email' => $this->email,
				'firstName' => $this->first_name === '' ? null : $this->first_name,
				'loginUrl' => $this->login_url ?? null
			]);
		}

		return true;
	}

	public function getPerson(): Person
	{
		return $this->person;
	}
}
