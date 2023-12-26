<?php

namespace app\modules\orders\formModels;

use app\modules\orders\models\Orders;
use app\modules\user\formModels\PersonAddressForm;
use app\modules\user\models\Person;
use app\modules\user\models\PersonAddress;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\validators\InlineValidator;
use yii\web\User;
use Yii;

class OrderAddressesForm extends Model
{
	public $order_id;

	public $required_addresses;

	public $shipping_address;

	public $billing_address;

	public $billing_address_the_same;

	protected Orders|null $order = null;

	protected ?Person $person;

	protected ?PersonAddressForm $shippingAddressForm = null;
	protected ?PersonAddressForm $billingAddressForm = null;

	public function rules(): array
	{
		return [
			[['order_id'], 'required'],
			['order_id', 'validateOrder'],
			['order_id', 'validateCustomer'],
			['billing_address_the_same', 'boolean'],
			[['required_addresses'], 'each', 'rule' => ['in', 'range' => [PersonAddress::TYPE_SHIPPING, PersonAddress::TYPE_BILLING]]],
			['shipping_address', 'required', 'when' => fn() => in_array(PersonAddress::TYPE_SHIPPING, $this->required_addresses)],
			['billing_address', 'required', 'when' => fn() => in_array(PersonAddress::TYPE_BILLING, $this->required_addresses)],
			['shipping_address', 'validateAddress', 'params' => ['type' => PersonAddress::TYPE_SHIPPING]],
			['billing_address', 'validateAddress', 'params' => ['type' => PersonAddress::TYPE_BILLING]],
		];
	}

	public function save(): bool
	{
		if (!$this->validate()) {
			return false;
		}

		if (isset($this->shippingAddressForm)) {
			$this->shippingAddressForm->processSave();
			if ($this->billing_address_the_same) {
				$personAddress = $this->shippingAddressForm->getPersonAddress();
				$personAddress->refresh();
				$personAddress->copyToType(PersonAddress::TYPE_BILLING);
			}
		}

		if (isset($this->billingAddressForm)) {
			$this->billingAddressForm->processSave();
		}

		return true;
	}

	public function validateOrder()
	{
		/** @var Orders order */
		$this->order = Orders::find()
			->byPublicId($this->order_id)
			->one()
		;

		if (!$this->order) {
			$this->addError('order_id', 'Order not found');
		}
	}

	public function validateCustomer()
	{
		$person = null;
		/** @var User $customerUser */
		$customerUser = Yii::$app->customerUser;
		if (!$customerUser->isGuest) {
			/** @var Person $person */
			$person = $customerUser->getIdentity()->getPerson();
		} elseif ($this->order->customer_id) {
			$person = Person::findOne($this->order->customer_id);
		}

		if (!$person) {
			$this->addError('order_id', 'Customer must be assigned to the order or user should be logged in to process the action.');
			return;
		}

		$this->person = $person;
	}

	public function validateAddress(string $field, array $params, InlineValidator $validator, $value)
	{
		if (!is_array($value)) {
			$this->addError($field, 'Address should be an object');
			return;
		}

		$addressForm = new PersonAddressForm();
		$addressForm->setPerson($this->person);
		$addressForm->setAttributes(ArrayHelper::merge($value, [
			'type' => $params['type'],
		]));

		if (!$addressForm->validate()) {
			foreach ($addressForm->getErrors() as $key => $errors) {
				$this->addError($field . '.' . $key, $errors);
			}
		}

		if ($params['type'] === PersonAddress::TYPE_SHIPPING) {
			$this->shippingAddressForm = $addressForm;
		} else {
			$this->billingAddressForm = $addressForm;
		}
	}

	public function getOrder(): ?Orders
	{
		return $this->order;
	}
}
