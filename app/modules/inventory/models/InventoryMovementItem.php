<?php

namespace app\modules\inventory\models;

use Yii;

/**
 * This is the model class for table "inventory_movement_item".
 *
 * @property int $movement_item_id
 * @property int $movement_id
 * @property int $item_id
 * @property int|null $from_location_id
 * @property int|null $to_location_id
 * @property int $available_qty_diff
 * @property int|null $reserved_qty_diff
 *
 * @property InventoryLocation $fromLocation
 * @property InventoryItem $item
 * @property InventoryMovement $movement
 * @property InventoryLocation $toLocation
 */
class InventoryMovementItem extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'inventory_movement_item';
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
			[['movement_id', 'item_id', 'available_qty_diff'], 'required'],
			[['movement_id', 'item_id', 'from_location_id', 'to_location_id', 'available_qty_diff', 'reserved_qty_diff'], 'default', 'value' => null],
			[['movement_id', 'item_id', 'from_location_id', 'to_location_id', 'available_qty_diff', 'reserved_qty_diff'], 'integer'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'movement_item_id' => 'Movement Item ID',
			'movement_id' => 'Movement ID',
			'item_id' => 'Item ID',
			'from_location_id' => 'From Location ID',
			'to_location_id' => 'To Location ID',
			'available_qty_diff' => 'Available Qty Diff',
			'reserved_qty_diff' => 'Reserved Qty Diff',
		];
	}

	/**
	 * Gets query for [[FromLocation]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getFromLocation()
	{
		return $this->hasOne(InventoryLocation::class, ['location_id' => 'from_location_id']);
	}

	/**
	 * Gets query for [[Item]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getItem()
	{
		return $this->hasOne(InventoryItem::class, ['item_id' => 'item_id']);
	}

	/**
	 * Gets query for [[Movement]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getMovement()
	{
		return $this->hasOne(InventoryMovement::class, ['movement_id' => 'movement_id']);
	}

	/**
	 * Gets query for [[ToLocation]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getToLocation()
	{
		return $this->hasOne(InventoryLocation::class, ['location_id' => 'to_location_id']);
	}
}
