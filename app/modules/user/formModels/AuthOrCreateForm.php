<?php

namespace app\modules\user\formModels;

use app\modules\orders\models\Basket;
use app\modules\system\models\Site;
use app\modules\user\models\PersonProfile;
use app\modules\user\traits\CustomerHelpers;
use app\modules\user\validators\CustomAttrsValidator;
use app\validators\UuidValidator;
use yii\base\Model;
use Yii;
use app\modules\user\models\Person;
use yii\db\Expression;

class AuthOrCreateForm extends Model
{
	use CustomerHelpers;

	public $email;
	public $id;
	public $first_name;
	public $last_name;
	public $phone;
	public $custom_attrs;
	public $cart_id;

	protected ?Person $person;
	protected Basket|null $cart = null;

	public function rules(): array
	{
		return [
			['email', 'email'],
			['email', 'filter', 'filter' => 'strtolower'],
			['id', UuidValidator::class],
			['email', 'validateIdAndEmail', 'skipOnEmpty' => false],
			[['first_name', 'last_name'], 'string', 'max' => 1000],
			[['first_name', 'last_name'], 'trim'],
			['phone', 'trim'],
			['custom_attrs', CustomAttrsValidator::class],
			['cart_id', UuidValidator::class]
		];
	}

	public function process(): bool
	{
		if (!$this->validate()) {
			return false;
		}

		$query = Person::find()->where('registered_at is not null');
		if ($this->id) {
			$query->andWhere(['public_id' => $this->id]);
		} else if ($this->email) {
			$query->andWhere(['email' => $this->email]);
		}

		$this->person = $query->one();
		if (!$this->person) {
			$this->registerCustomer();
		}

		if ($this->cart_id) {
			$this->processCustomerCartOnLogin($this->person, $this->cart_id);
			$this->cart = Basket::findOrCreatePersonBasket($this->person);
			$this->cart->calcTotal();
		}

		return true;
	}

	protected function registerCustomer()
	{
		$this->person = new Person();
		$this->person->attributes = [
			'site_id' => Site::DEFAULT_SITE,
			'email' => $this->email != '' ? $this->email : null,
			'public_id' => $this->id ?? null,
			'registered_at' => new Expression('now()'),
		];
		$this->person->save(false);
		$this->person->setClientRoles();

		PersonProfile::updateAll([
			'first_name' => $this->first_name === '' ? null : $this->first_name,
			'last_name' => $this->last_name === '' ? null : $this->last_name,
			'phone' => $this->phone === '' ? null : $this->phone,
			'custom_attrs' => empty($this->custom_attrs) ? null : $this->custom_attrs
		], ['person_id' => $this->person->person_id]);

		$this->person->refresh();
	}

	public function validateIdAndEmail()
	{
		if ($this->email == '' && $this->id == '') {
			$this->addError('id', Yii::t('app', 'Either email or id should be set.'));
			return;
		}

		if ($this->email) {
			//validate uniquness
			$person = Person::find()
				->where(['email' => $this->email])
				->andWhere('registered_at is not null')
				->andWhere('public_id != :id', ['id' => $this->id])
				->one()
			;

			if ($person) {
				$this->addError('email', Yii::t('app', 'Email is already registered.'));
				return;
			}
		}
	}

	public function getPerson(): ?Person
	{
		return $this->person;
	}

	public function getCart(): Basket|null
	{
		return $this->cart;
	}
}
