<?php

namespace app\modules\user\formModels;

use app\modules\delivery\models\VwCountry;
use app\modules\system\models\Setting;
use app\modules\user\models\Person;
use app\modules\user\models\PersonAddress;
use app\modules\user\validators\PhoneValidator;
use yii\base\Model;
use Yii;

class PersonAddressForm extends Model
{
	public $type;
	public $is_default;
	public $first_name;
	public $last_name;
	public $company;
	public $address_line_1;
	public $address_line_2;
	public $city;
	public $state;
	public $country_id;
	public $zip;
	public $phone;
	public $comment;

	protected Person|null $person;

	protected PersonAddress|null $personAddress = null;

	protected string|null $forceType;

	protected bool $shallFillNamesByAddress = true;

	public function rules(): array
	{
		$out = [
			[['first_name', 'last_name'], 'string', 'max' => 100],
			[['comment'], 'string', 'max' => 1000],
			['phone', PhoneValidator::class],
			[['address_line_1', 'city', 'country_id', 'zip'], 'required'],
			[
				[
					'first_name', 'last_name', 'comment',  'address_line_1',  'city', 'state',
					'zip'
				],
				'trim'
			],
			[['country_id'], 'exist', 'targetClass' => VwCountry::class, 'targetAttribute' => ['country_id' => 'country_id']],
			['type', 'in', 'range' => [PersonAddress::TYPE_SHIPPING, PersonAddress::TYPE_BILLING]],
			['type', 'validateDefaultType', 'skipOnEmpty' => false]
		];

		$checkoutPageSettings = Setting::getCheckoutPage();
		if (in_array('last', $checkoutPageSettings['customerNameRequired'])) {
			$out[] = ['last_name', 'required'];
		}

		if (in_array('first', $checkoutPageSettings['customerNameRequired'])) {
			$out[] = ['first_name', 'required'];
		}

		if ($checkoutPageSettings['addressLine2'] == 'optional') {
			$out[] = ['address_line_2', 'trim'];
		} elseif ($checkoutPageSettings['addressLine2'] == 'required') {
			$out[] = ['address_line_2', 'required'];
		}

		if ($checkoutPageSettings['companyName'] == 'optional') {
			$out[] = ['company', 'trim'];
		} elseif ($checkoutPageSettings['companyName'] == 'required') {
			$out[] = ['company', 'required'];
		}

		return $out;
	}

	public function save(): bool
	{
		if (!$this->validate()) {
			return false;
		}

		if (!isset($this->person)) {
			throw new \RuntimeException('Person must be passed');
		}

		$this->processSave();

		return true;
	}

	public function processSave()
	{
		$this->makeAddressByType();
		$this->saveAddressAttrs();
	}

	public function getPerson()
	{
		return $this->person;
	}

	public function setPerson(Person $person): self
	{
		$this->person = $person;
		return $this;
	}

	public function setForceType($val): self
	{
		$this->forceType = $val;

		return $this;
	}

	public function validateDefaultType()
	{
		if (isset($this->forceType) && $this->forceType) {
			$this->type = $this->forceType;
		}
	}

	protected function saveAddressAttrs()
	{
		$this->personAddress->attributes = [
			'first_name' => $this->first_name,
			'last_name' => $this->last_name,
			'address_line_1' => $this->address_line_1,
			'city' => $this->city,
			'state' => $this->state,
			'country_id' => $this->country_id,
			'zip' => $this->zip,
			'phone' => $this->phone,
			'comment' => $this->comment,
		];

		if ($this->isAttributeSafe('company')) {
			$this->personAddress->company = $this->company;
		}

		if ($this->isAttributeSafe('address_line_2')) {
			$this->personAddress->address_line_2 = $this->address_line_2;
		}

		if (!$this->personAddress->save(false)) {
			throw new \RuntimeException('Cannot save personAddress:' . print_r($this->personAddress->getErrors(), 1));
		}

		$this->person->checkDefaultAddressExists();

		if ($this->shallFillNamesByAddress) {
			$this->person->fillNamesByAddress($this->personAddress);
		}
	}

	protected function makeAddressByType()
	{
		$type = $this->type ?? null;
		$this->personAddress = PersonAddress::find()
			->where([
				'person_id' => $this->person->person_id,
				'type' => $type
			])
			->one()
		;

		if ($this->personAddress) {
			return;
		}

		$this->personAddress = new PersonAddress();
		$this->personAddress->attributes = [
			'person_id' => $this->person->person_id,
			'type' => $type
		];
	}

	public function attributeLabels()
	{
		return [
			'country_id' => Yii::t('app', 'Country')
		];
	}

	public function getPersonAddress(): PersonAddress|null
	{
		return $this->personAddress;
	}

	public function setShallFillNamesByAddress(bool $val): self
	{
		$this->shallFillNamesByAddress = $val;
		return $this;
	}
}
