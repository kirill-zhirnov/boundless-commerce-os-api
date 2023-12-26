<?php

namespace app\modules\orders\models;

use app\modules\inventory\models\InventoryItem;
use app\modules\inventory\models\VwInventoryItem;
use app\modules\system\models\Lang;
use Yii;
use yii\db\Expression;

/**
 * This is the model class for table "basket_item".
 *
 * @property int $basket_item_id
 * @property int $basket_id
 * @property int $item_id
 * @property int $qty
 * @property int $item_price_id
 * @property string $created_at
 * @property string|null $deleted_at
 *
 * @property Basket $basket
 * @property InventoryItem $item
 * @property ItemPrice $itemPrice
 * @property VwInventoryItem $vwItem
 */
class BasketItem extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'basket_item';
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
			[['basket_id', 'item_id', 'item_price_id'], 'required'],
			[['basket_id', 'item_id', 'qty', 'item_price_id'], 'default', 'value' => null],
			[['basket_id', 'item_id', 'qty', 'item_price_id'], 'integer'],
			[['created_at', 'deleted_at'], 'safe'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'basket_item_id' => 'Basket Item ID',
			'basket_id' => 'Basket ID',
			'item_id' => 'Item ID',
			'qty' => 'Qty',
			'item_price_id' => 'Item Price ID',
			'created_at' => 'Created At',
			'deleted_at' => 'Deleted At',
		];
	}

	/**
	 * Gets query for [[Basket]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getBasket()
	{
		return $this->hasOne(Basket::class, ['basket_id' => 'basket_id']);
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

	public function markDeleted()
	{
		$this->deleted_at = new Expression('now()');
		if (!$this->save()) {
			throw new \RuntimeException('Cannot save basketItem in markDeleted: ' . $this->basket_item_id . ':' . print_r($this->getErrors(), 1));
		}
	}

	public static function updateItemPrice(int $itemId, int $priceId, float|string $price)
	{
		self::getDb()
			->createCommand("
				update
					item_price
				set
					basic_price = :price,
					final_price = :price,
					discount_amount = null,
					discount_percent = null
				where
					item_price_id in (
						select
							item_price_id
						from
							basket_item
							inner join basket using(basket_id)
						where
							basket.is_active is true
							and basket_item.item_id = :itemId
							and basket_item.deleted_at is null
					)
					and price_id = :priceId
			")
			->bindValues([
				'itemId' => $itemId,
				'priceId' => $priceId,
				'price' => $price
			])
			->execute()
		;
	}

	public function fields(): array
	{
		$out = parent::fields();
		unset($out['basket_id'], $out['deleted_at']);

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
