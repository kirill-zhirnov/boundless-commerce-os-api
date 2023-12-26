<?php

namespace app\modules\orders\models;

use Yii;
use app\modules\delivery\models\Delivery;

/**
 * This is the model class for table "order_service_delivery".
 *
 * @property int $order_service_id
 * @property int|null $delivery_id
 * @property string|null $title
 * @property string|null $text_info
 * @property string|null $data
 *
 * @property Delivery $delivery
 * @property OrderService $orderService
 */
class OrderServiceDelivery extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'order_service_delivery';
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
			[['order_service_id'], 'required'],
			[['order_service_id', 'delivery_id'], 'default', 'value' => null],
			[['order_service_id', 'delivery_id'], 'integer'],
			[['data'], 'safe'],
			[['title'], 'string', 'max' => 255],
			[['text_info'], 'string', 'max' => 1000],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'order_service_id' => 'Order Service ID',
			'delivery_id' => 'Delivery ID',
			'title' => 'Title',
			'text_info' => 'Text Info',
			'data' => 'Data',
		];
	}

	/**
	 * Gets query for [[Delivery]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getDelivery()
	{
		return $this->hasOne(Delivery::class, ['delivery_id' => 'delivery_id']);
	}

	/**
	 * Gets query for [[OrderService]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrderService()
	{
		return $this->hasOne(OrderService::class, ['order_service_id' => 'order_service_id']);
	}

	public function saveDeliveryId($deliveryId)
	{
		$this->attributes = [
			'delivery_id' => $deliveryId,
			'title' => null,
			'text_info' => null,
			'data' => null
		];

		if (!$this->save(false)) {
			throw new \RuntimeException('Cannot save delivery_id:' . print_r($this->getErrors(), 1));
		}
	}

	public function fields(): array
	{
		$out = [
			'delivery_id',
			'title',
			'text_info',
			'data'
		];

		if ($this->isRelationPopulated('delivery')) {
			$out['delivery'] = function () {
				return $this->delivery;
			};
		}

		return $out;
	}
}
