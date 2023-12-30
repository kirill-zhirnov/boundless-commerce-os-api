<?php

namespace app\modules\inventory\models;

use app\helpers\Util;
use app\modules\catalog\models\Label;
use app\modules\catalog\models\Product;
use app\modules\inventory\activeQueries\VwInventoryItemQuery;
use app\modules\orders\models\BasketItem;
use Yii;

/**
 * This is the model class for table "vw_inventory_item".
 *
 * @property int|null $item_id
 * @property string|null $type
 * @property bool|null $track_inventory
 * @property int|null $available_qty
 * @property int|null $reserved_qty
 * @property int|null $product_id
 * @property int|null $variant_id
 * @property int|null $custom_item_id
 * @property string|null $status
 * @property string|null $deleted_at
 * @property int|null $lang_id
 * @property string|null $product
 * @property string|null $variant
 * @property string|null $custom_item
 * @property string|null $commodity_group
 * @property string|null $image
 * @property string|null $prices
 *
 * @property Label[] $labels
 * @property BasketItem[] $basketItems
 */
class VwInventoryItem extends \yii\db\ActiveRecord
{
	const TYPE_CUSTOM_ITEM = 'custom_item';
	const TYPE_PRODUCT = 'product';
	const TYPE_VARIANT = 'variant';

	const PRICE_SELLING_PRICE = 'selling_price';

	public static array|bool $exposePrices = [self::PRICE_SELLING_PRICE];

	protected array|bool $_exposePrices;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'vw_inventory_item';
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
	public static function primaryKey()
	{
		return ['item_id'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['item_id', 'available_qty', 'reserved_qty', 'product_id', 'variant_id', 'custom_item_id', 'lang_id'], 'default', 'value' => null],
			[['item_id', 'available_qty', 'reserved_qty', 'product_id', 'variant_id', 'custom_item_id', 'lang_id'], 'integer'],
			[['type', 'status'], 'string'],
			[['track_inventory'], 'boolean'],
			[['deleted_at', 'product', 'variant', 'custom_item', 'commodity_group', 'image', 'prices'], 'safe'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'item_id' => 'Item ID',
			'type' => 'Type',
			'track_inventory' => 'Track Inventory',
			'available_qty' => 'Available Qty',
			'reserved_qty' => 'Reserved Qty',
			'product_id' => 'Product ID',
			'variant_id' => 'Variant ID',
			'custom_item_id' => 'Custom Item ID',
			'status' => 'Status',
			'deleted_at' => 'Deleted At',
			'lang_id' => 'Lang ID',
			'product' => 'Product',
			'variant' => 'Variant',
			'custom_item' => 'Custom Item',
			'commodity_group' => 'Commodity Group',
			'image' => 'Image',
			'prices' => 'Prices',
		];
	}

	public function afterFind()
	{
		parent::afterFind();

		$this->prices = Util::sqlAggArr2Objects($this->prices);

		if (self::$exposePrices) {
			$this->_exposePrices = self::$exposePrices;
		}
	}

	/**
	 * Gets query for [[Labels]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLabels()
	{
		return $this->hasMany(Label::class, ['label_id' => 'label_id'])
			->viaTable('product_label_rel', ['product_id' => 'product_id']);
	}

	public function isCustomItem(): bool
	{
		return $this->type == self::TYPE_CUSTOM_ITEM;
	}

	public function isVariant(): bool
	{
		return $this->type == self::TYPE_VARIANT;
	}

	public function isInStock(): bool
	{
		if ($this->isCustomItem()) {
			return true;
		}

		return $this->available_qty > 0;
	}

	public function shallTrackInventory(): bool
	{
		return (bool) $this->track_inventory;
	}

	public function isInActive(): bool
	{
		if ($this->isCustomItem()) {
			return false;
		} else {
			return $this->status !== Product::STATUS_PUBLISHED || $this->deleted_at;
		}
	}

	public static function find(): VwInventoryItemQuery
	{
		return new VwInventoryItemQuery(get_called_class());
	}

	public function getTitle()
	{
		switch ($this->type) {
			case self::TYPE_CUSTOM_ITEM:
				return $this->custom_item['title'];

			case self::TYPE_PRODUCT:
				return $this->product['title'];

			case self::TYPE_VARIANT:
				return "{$this->product['title']}, {$this->variant['title']}";
		}
	}

	public function getPrice(string $alias): array|null
	{
		foreach ($this->prices as $price) {
			if (isset($price['alias']) && $price['alias'] == $alias) {
				return $price;
			}
		}

		return null;
	}

	public function getSellingPrice(): array|null
	{
		return $this->getPrice(self::PRICE_SELLING_PRICE);
	}

	public function getBasketItems()
	{
		return $this->hasMany(BasketItem::class, ['item_id' => 'item_id']);
	}

	public function fields(): array
	{
		$out = parent::fields();
		unset($out['lang_id']);

		if (empty($this->product['product_id'])) {
			unset($out['product']);
		}

		if (empty($this->variant['variant_id'])) {
			unset($out['variant']);
		}

		if (empty($this->custom_item['custom_item_id'])) {
			unset($out['custom_item']);
		}

		if (empty($this->commodity_group['group_id'])) {
			unset($out['commodity_group']);
		} else {
			$out['commodity_group'] = function (self $model) {
				$commodityGroup = $model->commodity_group;
				$commodityGroup['trackInventory'] = !$commodityGroup['not_track_inventory'];

				unset($commodityGroup['type'], $commodityGroup['vat'], $commodityGroup['not_track_inventory']);

				return $commodityGroup;
			};
		}

		if (isset($this->_exposePrices) && $this->_exposePrices) {
			$out['prices'] = function (self $model) {
				if ($this->_exposePrices === true) {
					return $model->prices;
				} else {
					return array_values(array_filter($model->prices, function ($value) {
						return in_array($value['alias'], $this->_exposePrices);
					}));
				}
			};
		} else {
			unset($out['prices']);
		}

		if ($this->isRelationPopulated('labels')) {
			$out['labels'] = function (self $model) {
				return $model->labels;
			};
		}

		$out['image'] = function (self $model) {
			if (!empty($model->image['path'])) {
				return $model->image;
			} else {
				return null;
			}
		};

		return $out;
	}
}
