<?php

namespace app\modules\orders\formModels;

use app\modules\orders\components\CheckoutStepper;
use app\modules\orders\components\OrderItems;
use app\modules\orders\models\Basket;
use app\modules\orders\models\CouponCampaign;
use app\modules\orders\models\Orders;
use app\modules\orders\validators\MinOrderAmountValidator;
use app\modules\system\models\Setting;
use app\validators\UuidValidator;
use yii\base\Model;
use Yii;

class CheckoutInit extends Model
{
	public $cart_id;

	protected Basket|null $basket;

	public function rules(): array
	{
		return [
			[['cart_id'], 'required'],
			[['cart_id'], UuidValidator::class],
			[['cart_id'], 'validateBasket'],
			//refactor me:
//			[
//				['cart_id'],
//				MinOrderAmountValidator::class,
//				'skipOnEmpty' => false,
//				'basket' => $this->basket
//			],
			[
				['cart_id'],
				'validateCheckoutSettings'
			],
		];
	}

	public function init()
	{
		if (!$this->validate()) {
			return false;
		}

		$this->basket->bindDraftOrder();

		/** @var Orders $order */
		$order = Orders::find()
			->publicOrderScope()
			->where(['basket_id' => $this->basket->basket_id])
			->one()
		;

		$orderItems = new OrderItems($order, $this->basket);

		$checkoutPageSettings = Setting::getCheckoutPage();

		$loggedInCustomer = null;
		if (!Yii::$app->customerUser->isGuest) {
			$loggedInCustomer = Yii::$app->customerUser->getIdentity()->getPerson();
		}

		$couponCampaignsQty = CouponCampaign::find()->where(['deleted_at' => null])->count();

		$items = $orderItems->getItems();
		$needShipping = false;
		foreach ($items as $item) {
			if ($item['vwItem']['commodity_group']['physical_products']) {
				$needShipping = true;
			}
		}

		$stepper = new CheckoutStepper($order, $items);

		return [
//			'cart' => $this->basket,
			'order' => $order,
			'items' => $items,
			'settings' => $checkoutPageSettings,
			'currency' => Setting::getCurrency(),
			'localeSettings' => Setting::getLocaleSettings(),
			'taxSettings' => Setting::getSystemTax(),
			'loggedInCustomer' => $loggedInCustomer,
			'hasCouponCampaigns' => $couponCampaignsQty > 0,
			'needShipping' => $needShipping,
			'stepper' => $stepper,
			'total' => $orderItems->calcTotal()
		];
	}

	public function validateCheckoutSettings()
	{
		$minOrderAmount = Setting::getMinOrderAmount();
		if (!empty($minOrderAmount) && is_numeric($minOrderAmount) && $minOrderAmount > 0) {
			$orderItems = new OrderItems(basket: $this->basket);
			$total = $orderItems->calcTotal();
			if (bccomp($minOrderAmount, $total['price'], 2) == 1) {
				$currency = Setting::getCurrencyAlias();
				/** @var \yii\i18n\Formatter $formatter */
				$formatter = Yii::$app->formatter;

				$this->addError(
					'cart_id',
					Yii::t('app', 'The minimal order amount is {minPrice}. Please add some products to the cart.', [
						'minPrice' => $formatter->asCurrency($minOrderAmount, $currency)
					])
				);
				return;
			}
		}
	}

	public function validateBasket()
	{
		$this->basket = Basket::find()
			->where([
				'public_id' => $this->cart_id,
				'is_active' => true
			])
			->one()
		;

		if (!$this->basket) {
			$this->addError('cart_id', 'Cart not found');
			return;
		}

//		if ($this->basket->order && !$this->basket->order->isDraft()) {
//			$this->addError('cart_id', 'Cart not found');
//			return;
//		}
	}
}
