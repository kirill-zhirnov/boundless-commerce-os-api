<?php

namespace app\modules\inventory\models;

use app\modules\orders\models\BasketItem;
use app\modules\system\models\Currency;
use Yii;
use app\modules\catalog\models\Product;
use app\modules\catalog\models\Price;
use app\modules\catalog\models\FinalPrice;

/**
 * This is the model class for table "inventory_item".
 *
 * @property int $item_id
 * @property int|null $product_id
 * @property int|null $variant_id
 * @property int $available_qty
 * @property int $reserved_qty
 * @property int|null $custom_item_id
 *
 * @property BasketItem[] $basketItems
 * @property Basket[] $baskets
 * @property FinalPrice[] $finalPrices
 * @property InventoryMovementItem[] $inventoryMovementItems
 * @property InventoryPrice[] $inventoryPrices
 * @property InventoryStock[] $inventoryStocks
 * @property InventoryLocation[] $locations
 * @property Price[] $prices
 * @property Product $product
 * @property ReserveItem[] $reserveItems
 * @property TransferItem[] $transferItems
 * @property Transfer[] $transfers
 * @property Variant $variant
 * @property VwTrackInventory $vwTrackInventory
 */
class InventoryItem extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'inventory_item';
	}

	/**
	 * @return \yii\db\Connection the database connection used by this AR class.
	 */
	public static function getDb()
	{
		return Yii::$app->get('instanceDb');
	}

	public function rules(): array
	{
		return [
			[
				['product_id', 'variant_id', 'available_qty', 'reserved_qty', 'custom_item_id'],
				'integer'
			],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'item_id' => 'Item ID',
			'product_id' => 'Product ID',
			'variant_id' => 'Variant ID',
			'available_qty' => 'Available Qty',
			'reserved_qty' => 'Reserved Qty',
		];
	}

	public function getVwTrackInventory()
	{
		return $this->hasOne(VwTrackInventory::class, ['item_id' => 'item_id']);
	}

	/**
	 * Gets query for [[BasketItems]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getBasketItems()
	{
		return $this->hasMany(BasketItem::className(), ['item_id' => 'item_id']);
	}

	/**
	 * Gets query for [[Baskets]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getBaskets()
	{
		return $this->hasMany(Basket::className(), ['basket_id' => 'basket_id'])
			->viaTable('basket_item', ['item_id' => 'item_id']);
	}

	/**
	 * Gets query for [[FinalPrices]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getFinalPrices()
	{
		return $this->hasMany(FinalPrice::class, ['item_id' => 'item_id']);
	}

	/**
	 * Gets query for [[InventoryMovementItems]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInventoryMovementItems()
	{
		return $this->hasMany(InventoryMovementItem::className(), ['item_id' => 'item_id']);
	}

	/**
	 * Gets query for [[InventoryPrices]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInventoryPrices()
	{
		return $this->hasMany(InventoryPrice::class, ['item_id' => 'item_id']);
	}

	/**
	 * Gets query for [[InventoryStocks]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInventoryStocks()
	{
		return $this->hasMany(InventoryStock::className(), ['item_id' => 'item_id']);
	}

	/**
	 * Gets query for [[Locations]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLocations()
	{
		return $this->hasMany(InventoryLocation::className(), ['location_id' => 'location_id'])
			->viaTable('inventory_stock', ['item_id' => 'item_id']);
	}

	/**
	 * Gets query for [[Prices]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPrices()
	{
		return $this->hasMany(Price::class, ['price_id' => 'price_id'])
			->viaTable('inventory_price', ['item_id' => 'item_id']);
	}

	/**
	 * Gets query for [[Product]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProduct()
	{
		return $this->hasOne(Product::class, ['product_id' => 'product_id']);
	}

	/**
	 * Gets query for [[ReserveItems]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getReserveItems()
	{
		return $this->hasMany(ReserveItem::className(), ['item_id' => 'item_id']);
	}

	/**
	 * Gets query for [[TransferItems]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getTransferItems()
	{
		return $this->hasMany(TransferItem::className(), ['item_id' => 'item_id']);
	}

	/**
	 * Gets query for [[Transfers]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getTransfers()
	{
		return $this->hasMany(Transfer::className(), ['transfer_id' => 'transfer_id'])
			->viaTable('transfer_item', ['item_id' => 'item_id']);
	}

	/**
	 * Gets query for [[Variant]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getVariant()
	{
		return $this->hasOne(Variant::className(), ['variant_id' => 'variant_id']);
	}

	public static function reCalcAvailableQty(bool $trackInventory, int|null $productId = null, int|null $groupId = null)
	{
		if ($groupId) {
			$where = 'and (commodity_group ->> \'group_id\')::int = :groupId';
			$params = ['groupId' => $groupId];
		} else if ($productId) {
			$where = 'and vw_inventory_item.product_id = :productId';
			$params = ['productId' => $productId];
		} else {
			throw new \RuntimeException('productId or groupId must be passed');
		}

		if ($trackInventory) {
			$sql = "
				update inventory_item
				set
					available_qty = (
						select
							coalesce(sum(available_qty), 0)
						from
							inventory_stock
						where
							inventory_stock.item_id = inventory_item.item_id
					)
				from
					vw_inventory_item
				where
					vw_inventory_item.item_id = inventory_item.item_id
					and vw_inventory_item.lang_id = 1
					{$where}
			";
		} else {
			$sql = "
				update inventory_item
				set
					available_qty = (
						case
							when inventory_item.available_qty > 0 then 1
							else 0
						end
					)
				from
					vw_inventory_item
				where
					vw_inventory_item.item_id = inventory_item.item_id
					and vw_inventory_item.lang_id = 1
					{$where}
			";
		}

		self::getDb()->createCommand($sql)
			->bindValues($params)
			->execute();
	}

	public function setPrice(Price $priceRow, Currency $currency, float|string|null $priceValue, float|string|null $compareAtPrices = null): void
	{
		if (is_null($priceValue)) {
			InventoryPrice::deleteAll([
				'item_id' => $this->item_id,
				'price_id' => $priceRow->price_id
			]);
			return;
		}

		self::getDb()
			->createCommand("select set_inventory_price(:itemId, :priceId, :currencyId, :price, :oldPrice)")
			->bindValues([
				'itemId' => $this->item_id,
				'priceId' => $priceRow->price_id,
				'currencyId' => $currency->currency_id,
				'price' => $priceValue,
				'oldPrice' => $compareAtPrices
			])
			->execute()
		;
		BasketItem::updateItemPrice($this->item_id, $priceRow->price_id, $priceValue);
	}

	public function changeAvailableQty(InventoryLocation $location, int $qty, InventoryMovement $movement)
	{
		self::getDb()
			->createCommand("
				select inventory_change_available_qty(
					:movement,
					:location,
					:item,
					:qty
				)
			")
			->bindValues([
				'movement' => $movement->movement_id,
				'location' => $location->location_id,
				'item' => $this->item_id,
				'qty' => $qty
			])
			->execute()
		;
	}

	public static function updateItemsQty(array $itemIds, int $qty)
	{
		$db = self::getDb();
		$itemIds = array_map(fn ($val) => $db->quoteValue($val), $itemIds);

		self::getDb()
			->createCommand("
				update
					inventory_item
				set
					available_qty = :qty
				where
					item_id in (" . implode(',', $itemIds) . ")
			")
			->bindValues([
				'qty' => $qty
			])
			->execute()
		;
	}
}
