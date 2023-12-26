<?php

namespace app\modules\orders\models;

use app\modules\orders\activeQueries\OrdersQuery;
use app\modules\orders\components\OrderItems;
use app\modules\orders\components\TotalCalculator;
use Yii;
use app\modules\user\models\Person;
use app\modules\payment\models\PaymentMethod;
use app\modules\catalog\models\PointSale;
use app\modules\payment\models\PaymentTransaction;

/**
 * This is the model class for table "orders".
 *
 * @property int $order_id
 * @property int|null $source_id
 * @property int|null $status_id
 * @property int|null $point_id
 * @property int|null $customer_id
 * @property int|null $basket_id
 * @property int|null $payment_method_id
 * @property float|null $service_total_price
 * @property int $service_total_qty
 * @property float|null $total_price
 * @property int|null $created_by
 * @property string $created_at
 * @property string|null $confirmed_at
 * @property string|null $paid_at
 * @property float $payment_mark_up
 * @property float $discount_for_order
 * @property string|null $got_cash_at
 * @property string $publishing_status
 * @property string|null $tax_amount
 * @property array $tax_calculations
 *
 * @property Basket $basket
 * @property Person $customer
 * @property OrderDiscount[] $orderDiscounts
 * @property OrderHistory[] $orderHistories
 * @property OrderProp $orderProp
 * @property OrderService[] $orderServices
 * @property PaymentMethod $paymentMethod
 * @property PaymentTransaction[] $paymentTransactions
 * @property PointSale $point
 * @property Reserve $reserve
 * @property OrderSource $source
 * @property OrderStatus $status
 * @property TrackNumber[] $trackNumbers
 */
class Orders extends \yii\db\ActiveRecord
{
	const STATUS_PUBLISHED = 'published';
	const STATUS_DRAFT = 'draft';

	const CHECKOUT_ACCOUNT_POLICY_GUEST_AND_LOGIN = 'guest-and-login';
	const CHECKOUT_ACCOUNT_POLICY_GUEST = 'guest';
	const CHECKOUT_ACCOUNT_POLICY_LOGIN_REQUIRED = 'login-required';

	/**
	 * Items which will be output to JSON as "items" key.
	 */
	protected $fieldItems;

	protected $shallExportInternalId = false;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'orders';
	}

	/**
	 * @return \yii\db\Connection the database connection used by this AR class.
	 */
	public static function getDb()
	{
		return Yii::$app->get('instanceDb');
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['source_id', 'status_id', 'point_id', 'customer_id', 'basket_id', 'payment_method_id', 'service_total_qty', 'created_by'], 'default', 'value' => null],
			[['source_id', 'status_id', 'point_id', 'customer_id', 'basket_id', 'payment_method_id', 'service_total_qty', 'created_by'], 'integer'],
			[['service_total_price', 'total_price', 'payment_mark_up', 'discount_for_order', 'tax_amount'], 'number'],
			[['created_at', 'confirmed_at', 'paid_at', 'got_cash_at', 'tax_calculations'], 'safe'],
			[['publishing_status'], 'string'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'order_id' => 'Order ID',
			'source_id' => 'Source ID',
			'status_id' => 'Status ID',
			'point_id' => 'Point ID',
			'customer_id' => 'Customer ID',
			'basket_id' => 'Basket ID',
			'payment_method_id' => 'Payment Method ID',
			'service_total_price' => 'Service Total Price',
			'service_total_qty' => 'Service Total Qty',
			'total_price' => 'Total Price',
			'created_by' => 'Created By',
			'created_at' => 'Created At',
			'confirmed_at' => 'Confirmed At',
			'paid_at' => 'Paid At',
			'payment_mark_up' => 'Payment Mark Up',
			'discount_for_order' => 'Discount For Order',
			'got_cash_at' => 'Got Cash At',
			'publishing_status' => 'Publishing Status',
		];
	}

	public function updateTotalsByTotalCalculator(TotalCalculator $totalCalculator)
	{
		$total = $totalCalculator->calcTotal();

		$this->service_total_price = $total['servicesSubTotal']['price'];
		$this->service_total_qty = $total['servicesSubTotal']['qty'];
		$this->total_price = $total['price'];
		$this->payment_mark_up = $total['paymentMarkup'];
		$this->discount_for_order = $total['discount'];
		$this->tax_amount = $total['tax']['totalTaxAmount'];
		$this->tax_calculations = $total;

		if (!$this->save(false)) {
			throw new \RuntimeException('Cannot save orders total' . print_r($this->getErrors(), 1));
		}
	}

	public function makeOrderItems(): OrderItems
	{
		return new OrderItems($this);
	}

	public function needOrderShipping(): bool
	{
		$items = $this->makeOrderItems()->getItems();
		$needShipping = false;
		foreach ($items as $item) {
			if ($item['vwItem']['commodity_group']['physical_products']) {
				$needShipping = true;
			}
		}

		return $needShipping;
	}

	public function reCalcOrderTotal()
	{
		$this->updateTotalsByTotalCalculator($this->makeOrderItems()->getTotalCalculator());
	}

	/**
	 * Gets query for [[Basket]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getBasket()
	{
		return $this->hasOne(Basket::class, ['basket_id' => 'basket_id']);
	}

	/**
	 * Gets query for [[Customer]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCustomer()
	{
		return $this->hasOne(Person::class, ['person_id' => 'customer_id']);
	}

	/**
	 * Gets query for [[OrderDiscounts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrderDiscounts()
	{
		return $this->hasMany(OrderDiscount::class, ['order_id' => 'order_id']);
	}

	/**
	 * Gets query for [[OrderHistories]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrderHistories()
	{
		return $this->hasMany(OrderHistory::className(), ['order_id' => 'order_id']);
	}

	/**
	 * Gets query for [[OrderProp]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrderProp()
	{
		return $this->hasOne(OrderProp::class, ['order_id' => 'order_id']);
	}

	/**
	 * Gets query for [[OrderServices]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrderServices()
	{
		return $this->hasMany(OrderService::class, ['order_id' => 'order_id']);
	}

	/**
	 * Gets query for [[PaymentMethod]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPaymentMethod()
	{
		return $this->hasOne(PaymentMethod::class, ['payment_method_id' => 'payment_method_id']);
	}

	/**
	 * Gets query for [[PaymentTransactions]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPaymentTransactions()
	{
		return $this->hasMany(PaymentTransaction::class, ['order_id' => 'order_id']);
	}

	/**
	 * Gets query for [[Point]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPoint()
	{
		return $this->hasOne(PointSale::class, ['point_id' => 'point_id']);
	}

	/**
	 * Gets query for [[Reserve]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getReserve()
	{
		return $this->hasOne(Reserve::class, ['order_id' => 'order_id']);
	}

	/**
	 * Gets query for [[Source]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getSource()
	{
		return $this->hasOne(OrderSource::className(), ['source_id' => 'source_id']);
	}

	/**
	 * Gets query for [[Status]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getStatus()
	{
		return $this->hasOne(OrderStatus::class, ['status_id' => 'status_id']);
	}

	/**
	 * Gets query for [[TrackNumbers]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getTrackNumbers()
	{
		return $this->hasMany(TrackNumber::className(), ['order_id' => 'order_id']);
	}

	public function isDraft(): bool
	{
		return $this->publishing_status === self::STATUS_DRAFT;
	}

	public function findServiceDelivery(): OrderService|null
	{
		return OrderService::find()
			->with(['orderServiceDelivery.delivery'])
			->where([
				'order_id' => $this->order_id,
				'is_delivery' => true
			])
			->one()
		;
	}

	public function findOrCreateServiceDelivery(): OrderService
	{
		self::getDb()->createCommand('
			insert into order_service
				(order_id, qty, is_delivery)
			values
				(:orderId, 1, true)
			on conflict do nothing
		')
			->bindValues([
				'orderId' => $this->order_id
			])
			->execute()
		;

		return $this->findServiceDelivery();
	}

	public static function find(): OrdersQuery
	{
		return new OrdersQuery(get_called_class());
	}

	public function setFieldItems(array|null $items)
	{
		$this->fieldItems = $items;
		return $this;
	}

	public function setShallExportInternalId(bool $value): self
	{
		$this->shallExportInternalId = $value;
		return $this;
	}

	public function fields(): array
	{
		$out = [
			'id' => function (self $model) {
				return $model->public_id;
			},
//			'payment_method_id',
			'service_total_price',
			'payment_mark_up',
			'total_price',
			'discount_for_order',
			'tax_amount',
			'paid_at',
			'status' => fn () => $this->status,
//			'props' => fn () => $this->orderProp,
			'customer' => fn () => $this->customer,
			'discounts' => fn () => $this->orderDiscounts,
			'paymentMethod' => fn () => $this->paymentMethod
		];

		if ($this->shallExportInternalId) {
			$out['order_id'] = function (self $model) {
				return $model->order_id;
			};
		}

		if (isset($this->fieldItems)) {
			$out['items'] = function() {
				return $this->fieldItems;
			};
		}

		if ($this->isRelationPopulated('orderServices')) {
			$out['services'] = function() {
				return $this->orderServices;
			};
		}

		$out['client_comment'] = fn () => $this->orderProp->client_comment;
		$out['custom_attrs'] = fn () => $this->orderProp->custom_attrs;

		$out['tax_calculations'] = function () {
			$result = null;
			if ($this->tax_calculations) {
				$result = $this->tax_calculations;
				if (isset($result['taxSettings'])) {
					unset($result['taxSettings']);
				}

				if (isset($result['calcByAPI'])) {
					unset($result['calcByAPI']);
				}
			}

			return $result;
		};
		array_push($out, 'publishing_status');
		array_push($out, 'created_at');

		return $out;
	}
}
