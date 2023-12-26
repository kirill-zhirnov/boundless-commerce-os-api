<?php

namespace app\modules\orders\formModels;

use app\modules\orders\models\Orders;
use app\modules\orders\traits\CheckoutHelpers;
use app\modules\user\formModels\PersonAddressForm;
use app\modules\user\models\Person;
use app\validators\UuidValidator;
use yii\web\User;
use Yii;

class CheckoutShippingAddress extends PersonAddressForm
{
	use CheckoutHelpers;

	protected string|null $forceType = 'shipping';

	public $order_id;

	protected Orders|null $order;

	public function rules(): array
	{
		$out = parent::rules();

		$out[] = ['order_id', 'required'];
		$out[] = ['order_id', UuidValidator::class];
		$out[] = ['order_id', 'validateOrder'];

		return $out;
	}

	public function save(): bool
	{
		$out = parent::save();

		if ($out) {
			$profileChanged = false;
			$profile = $this->person->personProfile;
			if (empty($profile->first_name) && $this->first_name) {
				$profile->first_name = $this->first_name;
				$profileChanged = true;
			}

			if (empty($profile->last_name) && $this->last_name) {
				$profile->last_name = $this->last_name;
				$profileChanged = true;
			}

			if (empty($profile->phone) && $this->phone) {
				$profile->phone = $this->phone;
				$profileChanged = true;
			}

			if ($profileChanged) {
				$profile->save(false);
			}

			if ($this->order->customer_id != $this->person->person_id) {
				$this->order->customer_id = $this->person->person_id;
				if (!$this->order->save(false)) {
					throw new \RuntimeException('cant bind customer to order');
				}
			}
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

		$this->setPerson($person);
	}

	public function getOrder(): Orders|null
	{
		return $this->order;
	}
}
