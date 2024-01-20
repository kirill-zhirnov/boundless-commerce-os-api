<?php

namespace app\modules\catalog\models;

use app\modules\system\models\Lang;
use app\modules\user\models\CustomerGroup;
use Yii;
use app\modules\inventory\models\InventoryItem;
use app\modules\inventory\models\InventoryPrice;

/**
 * This is the model class for table "price".
 *
 * @property int $price_id
 * @property string $alias
 * @property int $sort
 * @property string $created_at
 * @property string|null $deleted_at
 * @property bool $has_old_price
 *
 * @property CustomerGroup[] $customerGroups
 * @property FinalPrice[] $finalPrices
 * @property InventoryPrice[] $inventoryPrices
 * @property ItemPrice[] $itemPrices
 * @property InventoryItem[] $items
 * @property PriceText $priceTextDefault
 * @property PriceGroupRel[] $priceGroupRels
 */
class Price extends \yii\db\ActiveRecord
{
	const ALIAS_SELLING_PRICE = 'selling_price';
	const ALIAS_PURCHASE_PRICE = 'purchase_price';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'price';
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
			[['alias', 'sort'], 'required'],
			[['alias'], 'string'],
			[['sort'], 'default', 'value' => null],
			[['sort'], 'integer'],
			[['created_at', 'deleted_at'], 'safe'],
			[['has_old_price'], 'boolean'],
			[['alias'], 'unique'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'price_id' => 'Price ID',
			'alias' => 'Alias',
			'sort' => 'Sort',
			'created_at' => 'Created At',
			'deleted_at' => 'Deleted At',
			'has_old_price' => 'Has Old Price',
		];
	}

	/**
	 * Gets query for [[FinalPrices]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getFinalPrices()
	{
		return $this->hasMany(FinalPrice::class, ['price_id' => 'price_id']);
	}

	/**
	 * Gets query for [[InventoryPrices]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInventoryPrices()
	{
		return $this->hasMany(InventoryPrice::class, ['price_id' => 'price_id']);
	}

	/**
	 * Gets query for [[ItemPrices]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getItemPrices()
	{
		return $this->hasMany(ItemPrice::className(), ['price_id' => 'price_id']);
	}

	/**
	 * Gets query for [[Items]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getItems()
	{
		return $this->hasMany(InventoryItem::class, ['item_id' => 'item_id'])->viaTable('inventory_price', ['price_id' => 'price_id']);
	}

	public function getPriceTextDefault()
	{
		return $this->hasOne(PriceText::class, ['price_id' => 'price_id'])
			->andWhere(['price_text.lang_id' => Lang::DEFAULT_LANG])
		;
	}

	public function getPriceGroupRels()
	{
		return $this->hasMany(PriceGroupRel::class, ['price_id' => 'price_id']);
	}

	public function getCustomerGroups()
	{
		return $this->hasMany(CustomerGroup::class, ['group_id' => 'group_id'])
			->viaTable('price_group_rel', ['price_id' => 'price_id'])
		;
	}

	public function isSellingPrice(): bool
	{
		return $this->alias === self::ALIAS_SELLING_PRICE;
	}

	public function fields(): array
	{
		$out = [
			'price_id',
			'title' => fn () => $this->priceTextDefault->title,
			'alias',
			'has_old_price',
			'is_public',
			'created_at',
			'deleted_at'
		];

		if ($this->isRelationPopulated('customerGroups')) {
			$out['customerGroups'] = fn () => $this->customerGroups;
		}

		return $out;
	}
}
