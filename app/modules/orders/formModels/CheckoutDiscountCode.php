<?php

namespace app\modules\orders\formModels;

use app\modules\orders\models\CouponCode;
use app\modules\orders\models\OrderDiscount;
use app\modules\orders\models\Orders;
use app\modules\orders\traits\CheckoutHelpers;
use app\modules\orders\validators\CouponCodeValidator;
use app\validators\UuidValidator;
use yii\base\Model;
use Yii;
use yii\web\User;

class CheckoutDiscountCode extends Model
{
	use CheckoutHelpers;

	public $code;

	public $order_id;

	protected Orders|null $order;

	public function rules(): array
	{
		/** @var User $customerUser */
		$customerUser = Yii::$app->customerUser;

		return [
			[['code', 'order_id'], 'required'],
			['order_id', UuidValidator::class],
			['order_id', 'validateOrder'],
			['code', 'trim'],
			['code', 'filter', 'filter' => 'strtolower'],
			[
				'code',
				CouponCodeValidator::class,
				'customerId' => $customerUser->isGuest ? null : $customerUser->getIdentity()->getPerson()->person_id,
				'orderPublicIdAttr' => 'order_id'
			]
		];
	}

	public function validateOrder()
	{
		$this->order = $this->findCheckoutOrder($this->order_id);
		if (!$this->order) {
			$this->addError('order_id', 'Order not found');
			return;
		}

		$orderDiscount = OrderDiscount::find()
			->where([
				'order_id' => $this->order->order_id,
				'source' => OrderDiscount::SOURCE_COUPON
			])
			->one()
		;

		if ($orderDiscount) {
			$this->addError('code', 'Coupon already applied.');
			return;
		}
	}

	public function save(): false|array
	{
		if (!$this->validate()) {
			return false;
		}

		/** @var CouponCode $code */
		$code = CouponCode::find()
			->with(['campaign'])
			->where(['coupon_code.code' => $this->code])
			->one()
		;

		if (!$code) {
			throw new \RuntimeException('Coupon Code is not found');
		}

		$orderDiscount = new OrderDiscount();
		$orderDiscount->attributes = [
			'order_id' => $this->order->order_id,
			'title' => Yii::t('app', 'Discount by coupon campaign "{title}"', [
				'title' => $code->campaign->title
			]),
			'discount_type' => $code->campaign->discount_type,
			'value' => $code->campaign->discount_value,
			'source' => OrderDiscount::SOURCE_COUPON,
			'code_id' => $code->code_id
		];
		if (!$orderDiscount->save(false)) {
			throw new \RuntimeException('Cannot save order discount');
		}

		$this->order->reCalcOrderTotal();

		$publicOrder = Orders::find()
			->publicOrderScope()
			->where(['orders.order_id' => $this->order->order_id])
			->one()
		;

		return [
			'order' => $publicOrder,
			'total' => $this->order->makeOrderItems()->calcTotal()
		];
	}
}
