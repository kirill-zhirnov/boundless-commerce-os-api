<?php

namespace app\modules\user\formModels;

use app\modules\system\models\Site;
use app\modules\user\models\Person;
use app\modules\orders\models\Basket;
use app\modules\orders\models\BasketItem;
use app\validators\UuidValidator;
use yii\base\Model;
use Yii;

class CustomerLoginForm extends Model
{
	public $email;
	public $password;
	public $cart_id;

	protected Person|null $person = null;
	protected Basket|null $cart = null;

	public function rules(): array
	{
		return [
			[['email', 'password'], 'required'],
			['email', 'email'],
			['email', 'tryToLogin'],
			['cart_id', UuidValidator::class]
		];
	}

	public function tryToLogin()
	{
		$this->person = Person::find()
			->innerJoinWith('personAuth')
			->publicPersonScope()
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

		if ($this->cart_id) {
			$this->processCustomerCart();
			$this->cart = Basket::findOrCreatePersonBasket($this->person);
			$this->cart->calcTotal();
		}
	}

	public function getPerson(): Person|null
	{
		return $this->person;
	}

	public function getCart(): Basket|null
	{
		return $this->cart;
	}

	protected function processCustomerCart()
	{
		$existingActiveCart = Basket::find()
			->where(['person_id' => $this->person->person_id, 'is_active' => true])
			->one()
		;

		/** @var Basket $guestCart */
		$guestCart = Basket::find()
			->where(['public_id' => $this->cart_id, 'is_active' => true, 'person_id' => null])
			->one()
		;

		if (!$guestCart) {
			return;
		}

		if (!$existingActiveCart) {
			Basket::updateAll(['person_id' => $this->person->person_id], [
				'basket_id' => $guestCart->basket_id,
				'person_id' => null
			]);
			return;
		}

		//if both cart exist - merge baskets - copy basket items from guest to existing Active
		$guestCart->copyItemsTo($existingActiveCart);
		$guestCart->makeInactive();
	}
}
