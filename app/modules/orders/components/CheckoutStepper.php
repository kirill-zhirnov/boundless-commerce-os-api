<?php

namespace app\modules\orders\components;

use app\modules\orders\models\Orders;
use app\modules\orders\models\OrderService;
use app\modules\system\models\Lang;
use app\modules\system\models\Setting;
use app\modules\user\models\Person;
use app\modules\user\models\PersonAddress;
use yii\base\Arrayable;
use yii\db\Query;

class CheckoutStepper implements Arrayable
{
	const STEP_CONTACT_INFO = 'contact-info';
	const STEP_SHIPPING_ADDRESS = 'shipping-address';
	const STEP_SHIPPING_METHOD = 'shipping-method';
	const STEP_PAYMENT_METHOD = 'payment-method';

	protected array $filledSteps = [];

	protected array $steps = [];

	protected string|null $nextStep = null;

//	protected array $summaryByStep = [];

	public function __construct(protected Orders $order, protected array $items)
	{
		$this->initStepsByOrder();
	}

	public function fields(): array
	{
		$out = [
			'steps' => $this->steps,
			'filledSteps' => $this->filledSteps,
			'currentStep' => $this->nextStep,
		];

//		if (!empty($this->summaryByStep)) {
//			$out['summaryByStep'] = $this->summaryByStep;
//		}

		return $out;
	}

	public function extraFields(): array
	{
		return [];
	}

	public function toArray(array $fields = [], array $expand = [], $recursive = true)
	{
		return $this->fields();
	}

	protected function initStepsByOrder()
	{
		$this->initSteps();

		foreach ($this->steps as $step) {
			switch ($step) {
				case self::STEP_CONTACT_INFO:
					if ($this->isContactInfoFilled()) {
						$this->filledSteps[] = self::STEP_CONTACT_INFO;
					} else {
						break 2;
					}
					break;

				case self::STEP_SHIPPING_ADDRESS:
					if ($this->isShippingAddressFilled()) {
						$this->filledSteps[] = self::STEP_SHIPPING_ADDRESS;
					} else {
						break 2;
					}
					break;

//				case self::STEP_SHIPPING_METHOD:
//					if ($this->isShippingMethodFilled()) {
//						$this->filledSteps[] = self::STEP_SHIPPING_METHOD;
//					} else {
//						break 2;
//					}
//					break;
			}
		}

		foreach ($this->steps as $step) {
			if (!in_array($step, $this->filledSteps)) {
				$this->nextStep = $step;
				break;
			}
		}
	}

	protected function isShippingMethodFilled(): bool
	{
		/** @var OrderService $orderService */
		$orderService = OrderService::find()
			->where(['is_delivery' => true])
//			->with(['orderServiceDelivery', 'orderServiceDelivery.delivery', 'orderServiceDelivery.delivery.deliveryText'])
			->one()
		;

		if ($orderService && $orderService->qty > 0) {
//			$this->summaryByStep[self::STEP_SHIPPING_METHOD] = $orderService->orderServiceDelivery?->delivery?->deliveryText?->title;
			return true;
		}

		return false;
	}

	protected function isShippingAddressFilled(): bool
	{
		/** @var PersonAddress $address */
		$address = PersonAddress::find()
			->where([
				'person_id' => $this->order->customer_id,
				'type' => PersonAddress::TYPE_SHIPPING
			])
			->one()
		;

		if ($address && $address->isFilled()) {
//			$this->summaryByStep[self::STEP_SHIPPING_ADDRESS] = $address->getShortRepresentation();
			return true;
		}

		return false;
	}

	protected function isContactInfoFilled(): bool
	{
		$checkoutPageSettings = Setting::getCheckoutPage();

		$requiredFields = [];
		if ($checkoutPageSettings['contactFields']['email']['required']) {
			$requiredFields[] = 'email';
		}
		if ($checkoutPageSettings['contactFields']['phone']['required']) {
			$requiredFields[] = 'phone';
		}

		$filledFields = [];
		$summary = [];

		/** @var Person $customer */
		$customer = $this->order->customer;
		if ($customer) {
			foreach ($requiredFields as $field) {
				switch ($field) {
					case 'email':
						if ($customer->email) {
							$filledFields[] = 'email';
							$summary['email'] = $customer->email;
						}
						break;
					case 'phone':
						if ($customer->personProfile->phone) {
							$filledFields[] = 'phone';
							$summary['phone'] = $customer->personProfile->phone;
						}
						break;
				}
			}

			if (sizeof($filledFields) === sizeof($requiredFields)) {
//				$this->summaryByStep[self::STEP_CONTACT_INFO] = $summary;
				return true;
			}
		}

		return false;
	}

	protected function initSteps()
	{
		$this->steps = [self::STEP_CONTACT_INFO];

		$needShipping = false;
		foreach ($this->items as $item) {
			if ($item['vwItem']['commodity_group']['physical_products']) {
				$needShipping = true;
			}
		}

		if ($needShipping) {
			$this->steps[] = self::STEP_SHIPPING_ADDRESS;
//			$this->steps[] = self::STEP_SHIPPING_METHOD;
		}

		$this->steps[] = self::STEP_PAYMENT_METHOD;
	}
}
