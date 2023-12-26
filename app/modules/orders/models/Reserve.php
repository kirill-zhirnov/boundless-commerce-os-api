<?php

namespace app\modules\orders\models;

use Yii;
use app\modules\inventory\models\InventoryItem;
use app\modules\inventory\models\InventoryMovement;

/**
 * This is the model class for table "reserve".
 *
 * @property int $reserve_id
 * @property int|null $order_id
 * @property int $total_qty
 * @property float|null $total_price
 * @property string $created_at
 * @property string|null $completed_at
 *
 * @property InventoryMovement[] $inventoryMovements
 * @property Orders $order
 * @property ReserveItem[] $reserveItems
 */
class Reserve extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'reserve';
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
			[['order_id', 'total_qty'], 'default', 'value' => null],
			[['order_id', 'total_qty'], 'integer'],
			[['total_price'], 'number'],
			[['created_at', 'completed_at'], 'safe'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'reserve_id' => 'Reserve ID',
			'order_id' => 'Order ID',
			'total_qty' => 'Total Qty',
			'total_price' => 'Total Price',
			'created_at' => 'Created At',
			'completed_at' => 'Completed At',
		];
	}

	/**
	 * Gets query for [[InventoryMovements]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInventoryMovements()
	{
		return $this->hasMany(InventoryMovement::class, ['reserve_id' => 'reserve_id']);
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
	 * Gets query for [[ReserveItems]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getReserveItems()
	{
		return $this->hasMany(ReserveItem::class, ['reserve_id' => 'reserve_id']);
	}

	public function isCompleted(): bool
	{
		return $this->completed_at !== null;
	}

	/**
	 * @return ReserveItem[]
	 */
	public function findReservedItems(): array
	{
		/** @var ReserveItem[] $rows */
		$rows = ReserveItem::find()
			->with(['vwItem', 'stock'])
			->where([
				'reserve_item.reserve_id' => $this->reserve_id
			])
			->orderBy(['reserve_item.created_at' => SORT_ASC])
			->all()
		;

		return $rows;
	}

	public static function createByOrderId(int $orderId): self
	{
		self::getDb()->createCommand("
			insert into reserve (order_id)
			values (:order)
			on conflict do nothing
		")
			->bindValues(['order' => $orderId])
			->execute()
		;

		return self::findOne(['order_id' => $orderId]);
	}
}
