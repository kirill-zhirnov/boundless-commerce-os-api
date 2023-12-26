<?php

namespace app\modules\inventory\models;

use Yii;
use app\modules\orders\models\ReserveItem;

/**
 * This is the model class for table "inventory_stock".
 *
 * @property int $stock_id
 * @property int $location_id
 * @property int $item_id
 * @property int|null $supply_id
 * @property int $available_qty
 * @property int $reserved_qty
 *
 * @property InventoryItem $item
 * @property InventoryLocation $location
 * @property ReserveItem[] $reserveItems
 * @property InventorySupply $supply
 */
class InventoryStock extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'inventory_stock';
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
			[['location_id', 'item_id'], 'required'],
			[['location_id', 'item_id', 'supply_id', 'available_qty', 'reserved_qty'], 'default', 'value' => null],
			[['location_id', 'item_id', 'supply_id', 'available_qty', 'reserved_qty'], 'integer'],
			[['location_id', 'item_id'], 'unique', 'targetAttribute' => ['location_id', 'item_id']],
			[['location_id', 'item_id', 'supply_id'], 'unique', 'targetAttribute' => ['location_id', 'item_id', 'supply_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'stock_id' => 'Stock ID',
			'location_id' => 'Location ID',
			'item_id' => 'Item ID',
			'supply_id' => 'Supply ID',
			'available_qty' => 'Available Qty',
			'reserved_qty' => 'Reserved Qty',
		];
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
	 * Gets query for [[Location]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLocation()
	{
		return $this->hasOne(InventoryLocation::class, ['location_id' => 'location_id']);
	}

	/**
	 * Gets query for [[ReserveItems]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getReserveItems()
	{
		return $this->hasMany(ReserveItem::class, ['stock_id' => 'stock_id']);
	}

	/**
	 * Gets query for [[Supply]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getSupply()
	{
		return $this->hasOne(InventorySupply::className(), ['supply_id' => 'supply_id']);
	}

	public function changeQty(int|null $availableQty = null, int|null $reservedQty = null): void
	{
		$fields = [];
		$values = [];

		if (!is_null($availableQty)) {
			$fields[] = 'available_qty = available_qty + :available';
			$values['available'] = $availableQty;
		}

		if (!is_null($reservedQty)) {
			$fields[] = 'reserved_qty = reserved_qty + :reserved';
			$values['reserved'] = $reservedQty;
		}

		if (empty($fields)) {
			throw new \RuntimeException('Either availableQty or reserved should be specified');
		}

		$values['stockId'] = $this->stock_id;
		self::getDb()->createCommand("
			update
				inventory_stock
			set
				" . implode(', ', $fields) . "
			where
				stock_id = :stockId
		")
			->bindValues($values)
			->execute()
		;
	}
}
