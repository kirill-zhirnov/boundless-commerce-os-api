<?php

namespace app\modules\orders\models;

use app\modules\system\models\Lang;
use Yii;

/**
 * This is the model class for table "order_status".
 *
 * @property int $status_id
 * @property int|null $parent_id
 * @property string|null $alias
 * @property string|null $background_color
 * @property string $stock_location
 * @property int $sort
 * @property string $created_at
 * @property string|null $deleted_at
 *
 * @property NotificationTemplate[] $notificationTemplates
 * @property OrderHistory[] $orderHistories
 * @property OrderStatusText[] $statusText
 * @property OrderStatus[] $orderStatuses
 * @property Orders[] $orders
 * @property OrderStatus $parent
 */
class OrderStatus extends \yii\db\ActiveRecord
{
	const STOCK_LOCATION_INSIDE = 'inside';
	const STOCK_LOCATION_OUTSIDE = 'outside';
	const STOCK_LOCATION_BASKET = 'basket';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'order_status';
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
			[['parent_id', 'sort'], 'default', 'value' => null],
			[['parent_id', 'sort'], 'integer'],
			[['alias', 'stock_location'], 'string'],
			[['sort'], 'required'],
			[['created_at', 'deleted_at'], 'safe'],
			[['background_color'], 'string', 'max' => 6],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'status_id' => 'Status ID',
			'parent_id' => 'Parent ID',
			'alias' => 'Alias',
			'background_color' => 'Background Color',
			'stock_location' => 'Stock Location',
			'sort' => 'Sort',
			'created_at' => 'Created At',
			'deleted_at' => 'Deleted At',
		];
	}

	/**
	 * Gets query for [[NotificationTemplates]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getNotificationTemplates()
	{
		return $this->hasMany(NotificationTemplate::className(), ['status_id' => 'status_id']);
	}

	/**
	 * Gets query for [[OrderHistories]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrderHistories()
	{
		return $this->hasMany(OrderHistory::className(), ['status_id' => 'status_id']);
	}

	/**
	 * Gets query for [[OrderStatusText]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getStatusText()
	{
		return $this->hasOne(OrderStatusText::class, ['status_id' => 'status_id'])
			->where(['order_status_text.lang_id' => Lang::DEFAULT_LANG]);
	}

	/**
	 * Gets query for [[Orders]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrders()
	{
		return $this->hasMany(Orders::class, ['status_id' => 'status_id']);
	}

	/**
	 * Gets query for [[Parent]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getParent()
	{
		return $this->hasOne(OrderStatus::class, ['status_id' => 'parent_id']);
	}

	public function isStockOutside(): bool
	{
		return $this->stock_location == self::STOCK_LOCATION_OUTSIDE;
	}

	public function fields(): array
	{
		$out = [
			'status_id',
			'alias',
			'title' => fn (self $model) => $model->statusText->title,
			'background_color',
			'stock_location',
			'sort',
			'created_at',
			'deleted_at'
		];

		return $out;
	}
}
