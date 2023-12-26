<?php

namespace app\modules\orders\models;

use Yii;

/**
 * This is the model class for table "order_service".
 *
 * @property int $order_service_id
 * @property int $order_id
 * @property int|null $service_id
 * @property int $qty
 * @property float|null $total_price
 * @property int|null $item_price_id
 * @property bool $is_delivery
 * @property string $created_at
 *
 * @property ItemPrice $itemPrice
 * @property Orders $order
 * @property OrderServiceDelivery $orderServiceDelivery
 * @property Service $service
 */
class OrderService extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'order_service';
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
			[['order_id'], 'required'],
			[['order_id', 'service_id', 'qty', 'item_price_id'], 'default', 'value' => null],
			[['order_id', 'service_id', 'qty', 'item_price_id'], 'integer'],
			[['total_price'], 'number'],
			[['is_delivery'], 'boolean'],
			[['created_at'], 'safe'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'order_service_id' => 'Order Service ID',
			'order_id' => 'Order ID',
			'service_id' => 'Service ID',
			'qty' => 'Qty',
			'total_price' => 'Total Price',
			'item_price_id' => 'Item Price ID',
			'is_delivery' => 'Is Delivery',
			'created_at' => 'Created At',
		];
	}

	/**
	 * Gets query for [[ItemPrice]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getItemPrice()
	{
		return $this->hasOne(ItemPrice::class, ['item_price_id' => 'item_price_id']);
	}

	/**
	 * Gets query for [[Order]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrder()
	{
		return $this->hasOne(Orders::class, ['order_id' => 'order_id']);
	}

	/**
	 * Gets query for [[OrderServiceDelivery]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrderServiceDelivery()
	{
		return $this->hasOne(OrderServiceDelivery::class, ['order_service_id' => 'order_service_id']);
	}

	/**
	 * Gets query for [[Service]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getService()
	{
		return $this->hasOne(Service::className(), ['service_id' => 'service_id']);
	}

	public function findOrCreateItemPrice(): ItemPrice
	{
		if ($this->item_price_id) {
			return ItemPrice::findOne($this->item_price_id);
		} else {
			$itemPrice = new ItemPrice();
			if (!$itemPrice->save()) {
				throw new \RuntimeException('Cannot create empty item_price');
			}

			$this->item_price_id = $itemPrice->item_price_id;
			return $itemPrice;
		}
	}

	public function fields(): array
	{
		$out = [
			'order_service_id',
			'service_id',
			'qty',
      'total_price',
      'item_price_id',
      'is_delivery'
		];

		if ($this->isRelationPopulated('orderServiceDelivery')) {
			$out['serviceDelivery'] = function () {
				return $this->orderServiceDelivery;
			};
		}

		return $out;
	}
}
