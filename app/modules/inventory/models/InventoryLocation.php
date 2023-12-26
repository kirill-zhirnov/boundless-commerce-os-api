<?php

namespace app\modules\inventory\models;

use Yii;

/**
 * This is the model class for table "inventory_location".
 *
 * @property int $location_id
 * @property int|null $warehouse_id
 *
 * @property InventoryMovementItem[] $inventoryMovementItems
 * @property InventoryMovementItem[] $inventoryMovementItems0
 * @property InventoryStock[] $inventoryStocks
 * @property InventoryItem[] $items
 * @property Transfer[] $transfers
 * @property Transfer[] $transfers0
 * @property Warehouse $warehouse
 */
class InventoryLocation extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'inventory_location';
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
			[['warehouse_id'], 'default', 'value' => null],
			[['warehouse_id'], 'integer'],
			[['warehouse_id'], 'unique'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'location_id' => 'Location ID',
			'warehouse_id' => 'Warehouse ID',
		];
	}

	/**
	 * Gets query for [[InventoryMovementItems]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInventoryMovementItems()
	{
		return $this->hasMany(InventoryMovementItem::class, ['from_location_id' => 'location_id']);
	}

	/**
	 * Gets query for [[InventoryMovementItems0]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInventoryMovementItems0()
	{
		return $this->hasMany(InventoryMovementItem::class, ['to_location_id' => 'location_id']);
	}

	/**
	 * Gets query for [[InventoryStocks]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInventoryStocks()
	{
		return $this->hasMany(InventoryStock::class, ['location_id' => 'location_id']);
	}

	/**
	 * Gets query for [[Items]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getItems()
	{
		return $this->hasMany(InventoryItem::class, ['item_id' => 'item_id'])->viaTable('inventory_stock', ['location_id' => 'location_id']);
	}

	/**
	 * Gets query for [[Transfers]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getTransfers()
	{
		return $this->hasMany(Transfer::className(), ['from_location_id' => 'location_id']);
	}

	/**
	 * Gets query for [[Transfers0]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getTransfers0()
	{
		return $this->hasMany(Transfer::className(), ['to_location_id' => 'location_id']);
	}

	/**
	 * Gets query for [[Warehouse]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getWarehouse()
	{
		return $this->hasOne(Warehouse::className(), ['warehouse_id' => 'warehouse_id']);
	}
}
