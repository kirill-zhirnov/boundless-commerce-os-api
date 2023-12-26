<?php

namespace app\modules\orders\models;

use app\modules\inventory\models\VwInventoryItem;
use app\modules\system\models\Lang;
use Yii;
use app\modules\inventory\models\InventoryItem;
use app\modules\inventory\models\InventoryStock;

/**
 * This is the model class for table "reserve_item".
 *
 * @property int $reserve_item_id
 * @property int $reserve_id
 * @property int|null $stock_id
 * @property int $item_id
 * @property int $qty
 * @property float|null $total_price
 * @property int|null $item_price_id
 * @property string $created_at
 * @property string|null $completed_at
 *
 * @property InventoryItem $item
 * @property ItemPrice $itemPrice
 * @property Reserve $reserve
 * @property InventoryStock $stock
 * @property VwInventoryItem $vwItem
 */
class ReserveItem extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'reserve_item';
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
			[['reserve_id', 'item_id'], 'required'],
			[['reserve_id', 'stock_id', 'item_id', 'qty', 'item_price_id'], 'default', 'value' => null],
			[['reserve_id', 'stock_id', 'item_id', 'qty', 'item_price_id'], 'integer'],
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
			'reserve_item_id' => 'Reserve Item ID',
			'reserve_id' => 'Reserve ID',
			'stock_id' => 'Stock ID',
			'item_id' => 'Item ID',
			'qty' => 'Qty',
			'total_price' => 'Total Price',
			'item_price_id' => 'Item Price ID',
			'created_at' => 'Created At',
			'completed_at' => 'Completed At',
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

	public function getVwItem()
	{
		return $this->hasOne(VwInventoryItem::class, ['item_id' => 'item_id'])
			->where(['or', ['vw_inventory_item.lang_id' => Lang::DEFAULT_LANG], 'vw_inventory_item.lang_id is null'])
		;
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
	 * Gets query for [[Reserve]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getReserve()
	{
		return $this->hasOne(Reserve::class, ['reserve_id' => 'reserve_id']);
	}

	/**
	 * Gets query for [[Stock]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getStock()
	{
		return $this->hasOne(InventoryStock::class, ['stock_id' => 'stock_id']);
	}

	public function isCompleted(): bool
	{
		return $this->completed_at !== null;
	}

	public function fields(): array
	{
		$out = parent::fields();

		if ($this->isRelationPopulated('itemPrice') && $this->itemPrice) {
			$out['itemPrice'] = function () {
				return $this->itemPrice;
			};
		}

		if ($this->isRelationPopulated('vwItem') && $this->vwItem) {
			$out['vwItem'] = function () {
				return $this->vwItem;
			};
		}

		return $out;
	}
}
