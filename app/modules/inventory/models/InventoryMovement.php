<?php

namespace app\modules\inventory\models;

use app\modules\orders\models\Orders;
use Yii;
use app\modules\orders\models\Reserve;
use app\modules\user\models\Person;

/**
 * This is the model class for table "inventory_movement".
 *
 * @property int $movement_id
 * @property int $reason_id
 * @property int|null $person_id
 * @property int|null $reserve_id
 * @property string|null $props
 * @property string|null $notes
 * @property string $ts
 * @property int|null $order_id
 *
 * @property InventoryMovementItem[] $inventoryMovementItems
 * @property Person $person
 * @property InventoryOption $reason
 * @property Reserve $reserve
 * @property Transfer[] $transfers
 * @property Transfer[] $transfers0
 */
class InventoryMovement extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'inventory_movement';
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
			[['reason_id'], 'required'],
			[['reason_id', 'person_id', 'reserve_id', 'order_id'], 'default', 'value' => null],
			[['reason_id', 'person_id', 'reserve_id', 'order_id'], 'integer'],
			[['props', 'ts'], 'safe'],
			[['notes'], 'string']
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'movement_id' => 'Movement ID',
			'reason_id' => 'Reason ID',
			'person_id' => 'Person ID',
			'reserve_id' => 'Reserve ID',
			'props' => 'Props',
			'notes' => 'Notes',
			'ts' => 'Ts',
			'order_id' => 'Order ID',
		];
	}

	/**
	 * Gets query for [[InventoryMovementItems]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInventoryMovementItems()
	{
		return $this->hasMany(InventoryMovementItem::class, ['movement_id' => 'movement_id']);
	}

	/**
	 * Gets query for [[Person]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPerson()
	{
		return $this->hasOne(Person::class, ['person_id' => 'person_id']);
	}

	/**
	 * Gets query for [[Reason]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getReason()
	{
		return $this->hasOne(InventoryOption::class, ['option_id' => 'reason_id']);
	}

	/**
	 * Gets query for [[Reserve]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getReserve()
	{
		return $this->hasOne(Reserve::class, ['reserve_id' => 'reserve_id']);
	}

	/**
	 * Gets query for [[Transfers]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getTransfers()
	{
		return $this->hasMany(Transfer::className(), ['completed_movement_id' => 'movement_id']);
	}

	/**
	 * Gets query for [[Transfers0]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getTransfers0()
	{
		return $this->hasMany(Transfer::className(), ['cancelled_movement_id' => 'movement_id']);
	}

	public static function createByReason(
		string $category,
		string $alias,
		Reserve|null $reserve = null,
		Person|null $person = null,
		array|null $props = null,
		string|null $notes = null
	): self {
		$row = self::getDb()->createCommand("
			insert into inventory_movement
				(reason_id, person_id, reserve_id, props, notes, order_id)
			select
				option_id,
				:personId,
				:reserveId,
				:props,
				:notes,
				:orderId
			from
				inventory_option
			where
				category = :category
				and alias = :alias
			returning *
		")
			->bindValues([
				'personId' => $person?->person_id,
				'reserveId' => $reserve?->reserve_id,
				'orderId' => $reserve?->order_id,
				'props' => $props ? json_encode($props) : null,
				'notes' => $notes,
				'category' => $category,
				'alias' => $alias
			])
			->queryOne()
		;

		return self::findOne($row['movement_id']);
	}

	public function calcItems(): int
	{
		return self::getDb()->createCommand("
			select
				count(*)
			from
				inventory_movement_item
			where
				movement_id = :movement
		")
			->bindValues([
				'movement' => $this->movement_id
			])
			->queryScalar()
		;
	}

	public function destroyIfEmpty()
	{
		$totalItems = InventoryMovementItem::find()
			->where(['movement_id' => $this->movement_id])
			->count()
		;

		if ($totalItems == 0) {
			$this->delete();
		}
	}
}
