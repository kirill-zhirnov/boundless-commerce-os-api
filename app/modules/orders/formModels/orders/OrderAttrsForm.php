<?php

namespace app\modules\orders\formModels\orders;

use app\modules\delivery\models\Delivery;
use app\modules\inventory\models\VwInventoryItem;
use app\modules\orders\components\manipulatorForReserve\NotEnoughStockException;
use app\modules\orders\components\OrderCreator;
use app\modules\orders\models\Orders;
use app\modules\orders\models\OrderService;
use app\modules\payment\models\PaymentMethod;
use app\modules\system\models\Setting;
use app\modules\user\formModels\PersonAddressForm;
use app\modules\user\models\CustomerUser;
use app\modules\user\models\Person;
use app\modules\user\models\PersonAddress;
use app\modules\user\validators\PhoneValidator;
use app\validators\ArbitraryDataValidator;
use yii\base\Model;
use Yii;
use yii\helpers\ArrayHelper;
use yii\validators\EmailValidator;
use yii\validators\InlineValidator;

class OrderAttrsForm extends Model
{
	public $order_id;
	public $customer_id;
	public $contact;
	public $customer_custom_attrs;
	public $shipping_address;
	public $billing_address;
	public $billing_address_the_same;
	public $delivery_id;
	public $delivery_rate;
	public $payment_method_id;
	public $custom_attrs;
	public $client_comment;
	public $required_fields;
	public $place_the_order;

	protected ?Orders $order;
	protected ?PersonAddressForm $shippingAddressForm;
	protected ?PersonAddressForm $billingAddressForm;

	public function rules(): array
	{
		return [
			['customer_id', 'in', 'range' => ['me'], 'on' => 'customerForm'],
			['customer_id', 'validateCustomer', 'on' => 'customerForm'],
			['contact', 'validateContact', 'on' => 'customerForm'],
			['client_comment', 'string', 'max' => 3000, 'on' => 'customerForm'],
			['customer_custom_attrs', ArbitraryDataValidator::class, 'on' => 'customerForm'],
			[
				'required_fields', 'each',
				'rule' => ['in', 'range' => ['contact', 'client_comment', 'shipping_address', 'billing_address', 'payment_method_id', 'delivery_id']],
				'on' => 'customerForm'
			],
			['required_fields', 'validateRequiredFields', 'on' => 'customerForm'],
			['place_the_order', 'boolean', 'on' => 'customerForm'],

			['shipping_address', 'validateAddress', 'params' => ['type' => PersonAddress::TYPE_SHIPPING]],
			['billing_address', 'validateAddress', 'params' => ['type' => PersonAddress::TYPE_BILLING]],
			['billing_address_the_same', 'boolean'],

			[['payment_method_id', 'delivery_id'], 'integer', 'min' => 0],
			[
				'payment_method_id',
				'exist',
				'targetClass' => PaymentMethod::class,
				'targetAttribute' => ['payment_method_id' => 'payment_method_id']
			],
			[
				'delivery_id',
				'exist',
				'targetClass' => Delivery::class,
				'targetAttribute' => ['delivery_id' => 'delivery_id']
			],
			['delivery_id', 'required', 'when' => fn () => $this->delivery_rate],
			['delivery_rate', 'number', 'min' => 0],

			['custom_attrs', ArbitraryDataValidator::class]
		];
	}

	public function save(): bool
	{
		if (!isset($this->order)) {
			throw new \RuntimeException('Order should be set prior calling this func');
		}

		if (!$this->validate()) {
			return false;
		}

		$this->saveCustomer();
		$this->saveContact();
		if (!$this->saveCustomerCustomAttrs()) {
			return false;
		}
		$this->saveClientComment();
		$this->savePaymentMethod();
		$this->order->save(false);

		$this->saveAddresses();
		$this->saveDelivery();
		$this->saveCustomAttrs();

		$this->order->reCalcOrderTotal();

		/**
		 * Event will be emitted in OrderCreator
		 */
		if ($this->place_the_order) {
			$this->createOrderFromDraft();
		}

		return true;
	}

	protected function saveCustomAttrs()
	{
		if (!isset($this->custom_attrs)) {
			return;
		}

		$this->order->orderProp->extendCustomAttrs($this->custom_attrs);
	}

	protected function saveDelivery()
	{
		if (isset($this->delivery_id)) {
			$delivery = Delivery::findOne($this->delivery_id);
			$orderService = $this->order->findOrCreateServiceDelivery();

			$orderService
				->orderServiceDelivery
				->saveDeliveryId($this->delivery_id)
			;

			$itemPrice = $orderService->findOrCreateItemPrice();
			$price = (isset($delivery->shipping_config, $delivery->shipping_config['price'])) ? $delivery->shipping_config['price'] : 0;

			if (isset($this->delivery_rate)) {
				$price = $this->delivery_rate;
			}

			$itemPrice->saveSinglePrice($price);

			//trigger a trigger to recalculate total_price.
			OrderService::updateAll(['item_price_id' => $itemPrice->item_price_id], [
				'order_service_id' => $orderService->order_service_id
			]);
		}
	}

	protected function savePaymentMethod()
	{
		if (isset($this->payment_method_id)) {
			$this->order->payment_method_id = $this->payment_method_id;
		}
	}

	protected function saveAddresses()
	{
		if (!isset($this->shippingAddressForm) && !isset($this->billingAddressForm)) {
			return;
		}

		$person = $this->order->customer_id ? Person::findOne($this->order->customer_id) : Person::createGuestBuyer();
		$this->order->customer_id = $person->person_id;
		$this->order->save();

		if (isset($this->shippingAddressForm)) {
			$this->shippingAddressForm
				->setPerson($person)
				->processSave()
			;

			if ($this->billing_address_the_same) {
				$personAddress = $this->shippingAddressForm->getPersonAddress();
				$personAddress->refresh();
				$personAddress->copyToType(PersonAddress::TYPE_BILLING);
			}
		}

		if (isset($this->billingAddressForm)) {
			$this->billingAddressForm
				->setPerson($person)
				->processSave()
			;
		}
	}

	protected function createOrderFromDraft()
	{
		$orderCreator = new OrderCreator(
			$this->order,
			function(NotEnoughStockException $e, VwInventoryItem $item) {
				$error = Yii::t('app', 'Not enough stock for "{title}", requested: {requested}, available: {available}', [
					'title' => $item->getTitle(),
					'requested' => $e->requestedQty,
					'available' => $item->available_qty
				]);
				$this->addError('order_id', $error);
			}
		);
		$orderCreator->createFromDraft();
	}

	protected function saveClientComment()
	{
		if (!isset($this->client_comment)) {
			return;
		}

		$this->order->orderProp->client_comment = $this->client_comment;
		$this->order->orderProp->save(false);
	}

	protected function saveCustomerCustomAttrs(): bool
	{
		if (isset($this->customer_custom_attrs)) {
			if (
				empty($this->order->customer_id)
				|| !($person = Person::findOne($this->order->customer_id))
			) {
				$this->addError('customer_custom_attrs', Yii::t('app', 'Order doesnt have customer. Customer might be specified with customer_id or contact data.'));
				return false;
			}

			$person->personProfile->extendCustomAttrs($this->customer_custom_attrs);
		}

		return true;
	}

	protected function saveContact()
	{
		if (empty($this->contact)) {
			return;
		}

		$person = $this->order->customer_id ? Person::findOne($this->order->customer_id) : Person::createGuestBuyer();
		if (isset($this->contact['email'])) {
			$person->email = $this->contact['email'];
			$person->save(false);
		}

		$shouldSaveProfile = false;
		$profile = $person->personProfile;
		if (isset($this->contact['phone'])) {
			$profile->phone = $this->contact['phone'];
			$shouldSaveProfile = true;
		}

		if (isset($this->contact['first_name'])) {
			$profile->first_name = $this->contact['first_name'];
			$shouldSaveProfile = true;
		}

		if (isset($this->contact['last_name'])) {
			$profile->last_name = $this->contact['last_name'];
			$shouldSaveProfile = true;
		}

		if ($shouldSaveProfile) {
			$profile->save(false);
		}

		$this->order->customer_id = $person->person_id;

		return true;
	}

	protected function saveCustomer()
	{
		if ($this->customer_id === 'me') {
			/** @var CustomerUser $customerIdentity */
			$customerIdentity = Yii::$app->customerUser->getIdentity();

			$this->order->customer_id = $customerIdentity->getPerson()->person_id;
		}
	}

	public function validateCustomer()
	{
		if (!isset($this->customer_id) || $this->customer_id != 'me') {
			return;
		}

		/** @var \yii\web\User $customerUser */
		$customerUser = Yii::$app->customerUser;
		if ($customerUser->isGuest) {
			$this->addError('customer_id', Yii::t('app', "You are a Guest. Customer's token should be provided in X-Customer header."));
			return;
		}
	}

	public function validateContact()
	{
		if (!is_array($this->contact)) {
			$this->addError('contact', Yii::t('app', 'Contact should be an object.'));
			return;
		}

		$requiredKeys = [];
		$contactFields = Setting::getCheckoutPage()['contactFields'];
		if ($contactFields['email']['required']) {
			$requiredKeys[] = 'email';
		}
		if ($contactFields['phone']['required']) {
			$requiredKeys[] = 'phone';
		}

		$filteredValues = [];
		$possibleKeys = ['first_name', 'last_name', 'email', 'phone'];
		foreach ($possibleKeys as $field) {
			if (
				in_array($field, $requiredKeys)
				&& (!isset($this->contact[$field]) || trim($this->contact[$field]) == '')
			) {
				$this->addError('contact.' . $field, Yii::t('app', '{attribute} cannot be blank.', [
					'attribute' => 'contact.' . $field
				]));
				return;
			}

			if (!isset($this->contact[$field])) {
				continue;
			}

			switch ($field) {
				case 'email':
					$validator = new EmailValidator();
					if (!$validator->validate($this->contact[$field], $error)) {
						$this->addError('contact.' . $field, $error);
						return;
					}
					$filteredValues[$field] = mb_strtolower($this->contact[$field]);
					break;

				case 'phone':
					$validator = new PhoneValidator();
					if (!$validator->validate($this->contact[$field], $error)) {
						$this->addError('contact.' . $field, $error);
						return;
					}
					$filteredValues[$field] = trim($this->contact[$field]);
					break;
				case 'first_name':
				case 'last_name':
					$filteredValues[$field] = trim($this->contact[$field]);
					break;
			}
		}

		$this->contact = $filteredValues;
	}

	public function validateRequiredFields()
	{
		if (empty($this->required_fields)) {
			return;
		}

		foreach ($this->required_fields as $field) {
			if (empty($this->{$field})) {
				$this->addError($field, Yii::t('app', '{attribute} cannot be blank.', [
					'attribute' => $field
				]));
			}
		}
	}

	public function setOrder(?Orders $order): self
	{
		$this->order = $order;
		return $this;
	}

	public function validateAddress(string $field, array $params, InlineValidator $validator, $value)
	{
		if (!is_array($value)) {
			$this->addError($field, 'Address should be an object');
			return;
		}

		$addressForm = new PersonAddressForm();
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
}
