<?php

namespace app\modules\orders\validators;

use app\modules\orders\models\Orders;
use yii\db\Connection;
use yii\validators\Validator;
use Yii;
use yii\base\Model;

class CouponCodeValidator extends Validator
{
	public $orderPublicIdAttr;

	public $orderId;

	public $customerId;

	protected Orders|null $order;

	protected $orderTotal;

	protected array|false $code;

	public function validateAttribute($model, $attribute)
	{
		$value = $model->{$attribute};
		$this->code = $this->findCode($value);

		if (!$this->code) {
			$this->addError($model, $attribute, Yii::t('app', 'Discount code is not found.'));
			return;
		}

		if ($this->orderPublicIdAttr && $model->hasErrors($this->orderPublicIdAttr)) {
			return;
		}

		$this->order = $this->findOrder($model);
		if (!$this->order) {
			$this->addError($model, $attribute, Yii::t('app', 'Cant find order for discount validation'));
			return;
		}

		$total = $this->order->makeOrderItems()->calcTotal();

		if ($this->code['min_order_amount'] && $this->code['min_order_amount'] > $total['price']) {
			$this->addError($model, $attribute, Yii::t('app', 'Minimum order amount for given coupon is {amount}.', [
				'amount' => $this->code['min_order_amount']
			]));
			return;
		}

		$this->validateCouponLimits($model, $attribute);
	}

	protected function validateCouponLimits($model, $attribute)
	{
		if ((!$this->code['limit_usage_per_code'] && $this->code['limit_usage_per_customer']) || !$this->customerId) {
			return;
		}

		$row = $this->getDb()->createCommand("
			select
				count(*) coupon_used,
				coalesce(sum(
					case
						when customer_id = :customerId then 1
						else 0
					end
				), 0)::integer as customer_used
			from
				order_discount
				inner join orders using(order_id)
				inner join order_status using(status_id)
			where
				code_id = :codeId
				and order_status.alias not in ('cancelled')
		")
			->bindValues([
				'customerId' => $this->customerId,
				'codeId' => $this->code['code_id']
			])
			->queryOne()
		;

		if ($this->code['limit_usage_per_code'] && $row['coupon_used'] >= $this->code['limit_usage_per_code']) {
			$this->addError($model, $attribute, Yii::t('app', 'The discount code has already been used.'));
			return;
		}

		if ($this->code['limit_usage_per_customer'] && $row['customer_used'] >= $this->code['limit_usage_per_customer']) {
			$this->addError($model, $attribute, Yii::t('app', 'The discount code cannot be used more than {times} time(s) per customer.', [
				'times' => $this->code['limit_usage_per_customer']
			]));
			return;
		}
	}

	protected function findCode($code): array|false
	{
		$row = $this->getDb()->createCommand("
			select
				*
			from
				coupon_code
				inner join coupon_campaign using(campaign_id)
			where
				coupon_code.code = :code
				and coupon_campaign.deleted_at is null
		")
			->bindValues(['code' => $code])
			->queryOne()
		;

		return $row;
	}

	protected function findOrder(Model $model): Orders|null
	{
		$ordersQuery = Orders::find();

		if ($this->orderPublicIdAttr) {
			$publicId = $model->{$this->orderPublicIdAttr};
			$ordersQuery->where(['public_id' => $publicId]);
		} elseif ($this->orderId) {
			$ordersQuery->where(['order_id' => $this->orderId]);
		} else {
			throw new \RuntimeException('orderPublicIdAttr or orderId must be passed.');
		}

		return $ordersQuery->one();
	}

	protected function getDb(): Connection
	{
		return Yii::$app->instanceDb;
	}
}
