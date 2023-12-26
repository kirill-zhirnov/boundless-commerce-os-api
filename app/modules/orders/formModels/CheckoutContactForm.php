<?php

namespace app\modules\orders\formModels;

use app\components\InstancesQueue;
use app\helpers\Util;
use app\modules\orders\models\Orders;
use app\modules\orders\traits\CheckoutHelpers;
use app\modules\orders\validators\CustomerLoginStatusValidator;
use app\modules\system\models\Setting;
use app\modules\user\models\Person;
use app\modules\user\models\PersonAuth;
use app\modules\user\models\PersonProfile;
use app\modules\user\validators\PhoneValidator;
use app\validators\UuidValidator;
use yii\base\Model;
use app\modules\system\models\Site;
use Yii;
use yii\db\Expression;

class CheckoutContactForm extends Model
{
	use CheckoutHelpers;

	public $phone;

	public $email;

	public $order_id;

	public $receive_marketing_info;

	public $register_me;

	protected Orders|null $order;

	protected bool $userIsRegistered = false;

	public function rules(): array
	{
		/** @var \yii\web\User $customerUser */
		$customerUser = Yii::$app->customerUser;

		$contactFields = Setting::getCheckoutPage()['contactFields'];

		$out = [
			['order_id', 'required'],
			['order_id', UuidValidator::class],
			['order_id', 'validateOrder'],
			['order_id', CustomerLoginStatusValidator::class],
			['receive_marketing_info', 'boolean'],
			['register_me', 'boolean'],
		];

		if ($contactFields['phone']['show']) {
			$out[] = ['phone', PhoneValidator::class];

			if ($contactFields['phone']['required']) {
				$out[] = ['phone', 'required'];
			}
		}

		if ($customerUser->isGuest && $contactFields['email']['show']) {
			$out[] = ['email', 'email'];
			$out[] = ['email', 'filter', 'filter' => 'strtolower'];

			if ($contactFields['email']['required'] && $customerUser->isGuest) {
				$out[] = ['email', 'required'];
			}

			$out[] = [
				'email',
				'validateOnUniqueness',
				'skipOnEmpty' => false,
				'when' => fn () => $this->register_me
			];
		}

		return $out;
	}

	public function save(): false|array
	{
		if (!$this->validate()) {
			return false;
		}

		/** @var \yii\web\User $customerUser */
		$customerUser = Yii::$app->customerUser;

		if ($customerUser->isGuest) {
			$person = $this->makeBuyerForOrder();
		} else {
			$person = $customerUser->getIdentity()->getPerson();
		}

//		$sendWelcomeEmail = Setting::getCheckoutPage()['sendWelcomeEmail'];
//		if ($sendWelcomeEmail == 'always' && !$person->registered_at) {
//			$this->registerPerson($person);
//		}

		$this->updatePersonAttrs($person);

		$this->order->customer_id = $person->person_id;
		$this->order->save(false);

		/** @var Person $person */
		$person = Person::find()
			->publicPersonScope()
			->where(['person_id' => $person->person_id])
			->one()
		;

		$out = ['customer' => $person];

		if ($this->userIsRegistered) {
			$out['authToken'] = $person->createAuthToken();
		}

		return $out;
	}

	public function validateOrder()
	{
		$this->order = $this->findCheckoutOrder($this->order_id);
		if (!$this->order) {
			$this->addError('order_id', 'Order not found');
			return;
		}

		$accountPolicy = Setting::getCheckoutPage()['accountPolicy'];

		/** @var \yii\web\User $customerUser */
		$customerUser = Yii::$app->customerUser;
		if ($accountPolicy === Orders::CHECKOUT_ACCOUNT_POLICY_LOGIN_REQUIRED && $customerUser->isGuest) {
			$this->addError('order_id', 'You have to be logged in.');
			return;
		}
	}

	public function validateOnUniqueness()
	{
		if ($this->register_me && $this->email == '') {
			$this->addError('email', Yii::t('app', 'Email is required in case you want to become registered.'));
			return;
		}

		$query = Person::find()
			->where([
				'site_id' => Site::DEFAULT_SITE,
				'email' => $this->email
			])
			->andWhere('registered_at is not null')
		;

		$person = $query->one();
		if ($person) {
			$this->addError('email', Yii::t('app', 'User with such email has already been registered. Please login or enter another email.'));
			return;
		}
	}

	protected function makeBuyerForOrder(): Person
	{
		$person = new Person();
		$person->site_id = Site::DEFAULT_SITE;
		$person->email = $this->email ?? null;

		if ($this->register_me) {
			$person->registered_at = new Expression('now()');
		}

		$person->save(false);

		if ($this->register_me) {
			$person->setClientRoles();
			$this->userIsRegistered = true;
		} else {
			$person->setGuestBuyerRoles();
		}

		if ($this->register_me) {
			$this->sendWelcomeEmail($person);
		}

		return $person;
	}

	protected function sendWelcomeEmail(Person $person): void
	{
		$newPass = Util::getRndStr(6);
		PersonAuth::setPass($person->person_id, $newPass);

		/** @var InstancesQueue $queue */
		$queue = Yii::$app->queue;
		$queue->sendMail(InstancesQueue::MAIL_WELCOME_EMAIL, [
			'email' => $person->email,
			'pass' => $newPass
		]);
	}

	protected function updatePersonAttrs(Person $person): void
	{
		$attrs = [
			'receive_marketing_info' => (bool) $this->receive_marketing_info
		];
		if ($this->phone) {
			$attrs['phone'] = $this->phone;
		}

		if ($attrs) {
			PersonProfile::updateAll($attrs, ['person_id' => $person->person_id]);
		}
	}
}
