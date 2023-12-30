<?php

namespace app\modules\catalog\models;

use app\helpers\Util;
use app\modules\catalog\activeQueries\ProductQuery;
use Cocur\Slugify\Slugify;
use Yii;
use app\modules\inventory\models\InventoryItem;
use app\modules\system\models\Lang;
use app\modules\cms\models\Image;
use yii\db\ActiveQuery;
use app\modules\system\models\Setting;

/**
 * This is the model class for table "product".
 *
 * @property int $product_id
 * @property string|null $sku
 * @property int|null $manufacturer_id
 * @property int|null $group_id
 * @property string $created_at
 * @property string|null $deleted_at
 * @property bool $has_variants
 * @property string|null $external_id
 * @property string $status
 * @property int|null $created_by
 *
 * @property Category[] $categories
 * @property CharacteristicProductVal[] $characteristicProductVals
 * @property Characteristic[] $characteristics
 * @property Characteristic[] $characteristics0
 * @property CollectionProductRel[] $collectionProductRels
 * @property Collection[] $collections
 * @property Person $createdBy
 * @property CrossSell[] $crossSells
 * @property CrossSell[] $crossSells0
 * @property CommodityGroup $commodityGroup
 * @property Image[] $images
 * @property InventoryItem $inventoryItem
 * @property Label[] $labels
 * @property Lang[] $langs
 * @property Manufacturer $manufacturer
 * @property MenuItemRel[] $menuItemRels
 * @property Offer[] $offers
 * @property Orders[] $orders
 * @property ProductCategoryRel[] $productCategoryRels
 * @property ProductImage[] $productImages
 * @property ProductImportImgs[] $productImportImgs
 * @property ProductImportRel[] $productImportRels
 * @property ProductLabelRel[] $productLabelRels
 * @property ProductProp $productProp
 * @property ProductReview[] $productReviews
 * @property ProductText[] $productTexts
 * @property ProductText $productTextDefault
 * @property ProductVariantCharacteristic[] $productVariantCharacteristics
 * @property ProductYml $productYml
 * @property Variant[] $variants
 * @property Category $defaultCategory
 */
class Product extends \yii\db\ActiveRecord
{
	const STATUS_PUBLISHED = 'published';
	const STATUS_HIDDEN = 'hidden';

//	public string|array|null $__product_price;

	public $nonVariantCharacteristics;

	public $extendedVariants;

	public $compiledSeoProps;

	public $__item_id;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'product';
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
			[['sku', 'external_id', 'status'], 'string'],
			[['manufacturer_id', 'group_id', 'created_by'], 'default', 'value' => null],
			[['manufacturer_id', 'group_id', 'created_by'], 'integer'],
			[['created_at', 'deleted_at'], 'safe'],
			[['has_variants'], 'boolean'],
			[['external_id'], 'unique'],
			[['sku'], 'unique'],
			[['status', 'created_by'], 'unique', 'targetAttribute' => ['status', 'created_by']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'product_id' => 'Product ID',
			'sku' => 'Sku',
			'manufacturer_id' => 'Manufacturer ID',
			'group_id' => 'Group ID',
			'created_at' => 'Created At',
			'deleted_at' => 'Deleted At',
			'has_variants' => 'Has Variants',
			'external_id' => 'External ID',
			'status' => 'Status',
			'created_by' => 'Created By',
		];
	}

	public static function find(): ProductQuery
	{
		return new ProductQuery(get_called_class());
	}

	public function afterFind()
	{
		parent::afterFind();

//		if (isset($this->__product_price)) {
//			$this->__product_price = json_decode($this->__product_price, true);
//		}
	}

	/**
	 * Gets query for [[Categories]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategories()
	{
		return $this->hasMany(Category::class, ['category_id' => 'category_id'])
			->viaTable('product_category_rel', ['product_id' => 'product_id'], function (ActiveQuery $query) {
				$query->orderBy(['product_category_rel.sort' => SORT_ASC]);
			});
	}

	public function getDefaultCategory()
	{
		return $this->hasOne(Category::class, ['category_id' => 'category_id'])
			->viaTable('product_category_rel', ['product_id' => 'product_id'], function (ActiveQuery $query) {
				$query->where('product_category_rel.is_default is true');
			})
		;
	}

	/**
	 * Gets query for [[CharacteristicProductVals]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristicProductVals()
	{
		return $this->hasMany(CharacteristicProductVal::className(), ['product_id' => 'product_id']);
	}

	/**
	 * Gets query for [[Characteristics]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristics()
	{
		return $this->hasMany(Characteristic::class, ['characteristic_id' => 'characteristic_id'])->viaTable('characteristic_product_val', ['product_id' => 'product_id']);
	}

	/**
	 * Gets query for [[Characteristics0]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristics0()
	{
		return $this->hasMany(Characteristic::class, ['characteristic_id' => 'characteristic_id'])->viaTable('product_variant_characteristic', ['product_id' => 'product_id']);
	}

	/**
	 * Gets query for [[CollectionProductRels]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCollectionProductRels()
	{
		return $this->hasMany(CollectionProductRel::class, ['product_id' => 'product_id']);
	}

	/**
	 * Gets query for [[Collections]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCollections()
	{
		return $this->hasMany(Collection::class, ['collection_id' => 'collection_id'])
			->viaTable('collection_product_rel', ['product_id' => 'product_id'])
		;
	}

	/**
	 * Gets query for [[CreatedBy]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCreatedBy()
	{
		return $this->hasOne(Person::className(), ['person_id' => 'created_by']);
	}

	/**
	 * Gets query for [[CrossSells]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCrossSells()
	{
		return $this->hasMany(CrossSell::class, ['product_id' => 'product_id']);
	}

	/**
	 * Gets query for [[CrossSells0]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCrossSells0()
	{
		return $this->hasMany(CrossSell::class, ['rel_product_id' => 'product_id']);
	}

	/**
	 * Gets query for [[Group]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCommodityGroup()
	{
		return $this->hasOne(CommodityGroup::class, ['group_id' => 'group_id']);
	}

	/**
	 * Gets query for [[Images]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getImages()
	{
		return $this->hasMany(Image::class, ['image_id' => 'image_id'])->viaTable('product_image', ['product_id' => 'product_id']);
	}

	/**
	 * Gets query for [[InventoryItem]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInventoryItem()
	{
		return $this->hasOne(InventoryItem::class, ['product_id' => 'product_id']);
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

	/**
	 * Gets query for [[Langs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLangs()
	{
		return $this->hasMany(Lang::class, ['lang_id' => 'lang_id'])->viaTable('product_text', ['product_id' => 'product_id']);
	}

	//  public function getFinalPrices()
	//  {
	//      return $this->hasMany(FinalPrice::class, ['item_id' => 'item_id'])->viaTable('inventory_item', ['product_id' => 'product_id']);
	//  }

	/**
	 * Gets query for [[Manufacturer]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getManufacturer()
	{
		return $this->hasOne(Manufacturer::class, ['manufacturer_id' => 'manufacturer_id']);
	}

	/**
	 * Gets query for [[MenuItemRels]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getMenuItemRels()
	{
		return $this->hasMany(MenuItemRel::className(), ['product_id' => 'product_id']);
	}

	/**
	 * Gets query for [[Offers]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOffers()
	{
		return $this->hasMany(Offer::className(), ['product_id' => 'product_id']);
	}

	/**
	 * Gets query for [[Orders]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrders()
	{
		return $this->hasMany(Orders::className(), ['order_id' => 'order_id'])->viaTable('product_review', ['product_id' => 'product_id']);
	}

	/**
	 * Gets query for [[ProductCategoryRels]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductCategoryRels()
	{
		return $this->hasMany(ProductCategoryRel::class, ['product_id' => 'product_id']);
	}

	/**
	 * Gets query for [[ProductImages]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductImages()
	{
		return $this->hasMany(ProductImage::class, ['product_id' => 'product_id']);
	}

	/**
	 * Gets query for [[ProductImportImgs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductImportImgs()
	{
		return $this->hasMany(ProductImportImgs::className(), ['product_id' => 'product_id']);
	}

	/**
	 * Gets query for [[ProductImportRels]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductImportRels()
	{
		return $this->hasMany(ProductImportRel::className(), ['product_id' => 'product_id']);
	}

	/**
	 * Gets query for [[ProductLabelRels]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductLabelRels()
	{
		return $this->hasMany(ProductLabelRel::class, ['product_id' => 'product_id']);
	}

	/**
	 * Gets query for [[ProductProp]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductProp()
	{
		return $this->hasOne(ProductProp::class, ['product_id' => 'product_id']);
	}

	/**
	 * Gets query for [[ProductReviews]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductReviews()
	{
		return $this->hasMany(ProductReview::className(), ['product_id' => 'product_id']);
	}

	/**
	 * Gets query for [[ProductTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductTexts()
	{
		return $this->hasMany(ProductText::class, ['product_id' => 'product_id']);
	}

	public function getProductTextDefault()
	{
		return $this->hasOne(ProductText::class, ['product_id' => 'product_id'])
			->andWhere(['product_text.lang_id' => Lang::DEFAULT_LANG])
		;
	}

	/**
	 * Gets query for [[ProductVariantCharacteristics]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductVariantCharacteristics()
	{
		return $this->hasMany(ProductVariantCharacteristic::className(), ['product_id' => 'product_id']);
	}

	/**
	 * Gets query for [[ProductYml]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductYml()
	{
		return $this->hasOne(ProductYml::className(), ['product_id' => 'product_id']);
	}

	/**
	 * Gets query for [[Variants]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getVariants()
	{
		return $this->hasMany(Variant::class, ['product_id' => 'product_id']);
	}

	public function onGroupChanged(int $prevGroupId, int $newGroupId): void
	{
		/** @var CommodityGroup $prevGroup */
		$prevGroup = CommodityGroup::findOne($prevGroupId);
		/** @var CommodityGroup $newGroup */
		$newGroup = CommodityGroup::findOne($newGroupId);

		if ($prevGroup->not_track_inventory === $newGroup->not_track_inventory) {
			return;
		}

		InventoryItem::reCalcAvailableQty(!$newGroup->not_track_inventory, $this->product_id);
	}

	public static function findUniqueUrlKeyByTitle(string $title, int|null $productId = null): string
	{
		$slugify = new Slugify();
		$basicSlug = $slugify->slugify(mb_strtolower($title), '-');

		$i = 0;
		$rndLen = 5;
		while (true) {
			$slug = $basicSlug;
			if ($i > 0) {
				$rnd = Util::getRndStr($rndLen, 'letnum', false);
				$slug .= '-' . $rnd;
			}

			$query = ProductText::find()
				->where(['url_key' => $slug])
			;

			if ($productId) {
				$query->andWhere('product_id != :productId', ['productId' => $productId]);
			}

			$total = $query->count();
			if ($total == 0) {
				return $slug;
			} elseif ($i > 4) {
				$rndLen++;
			}

			$i++;
		}
	}
//	public function isInStock(bool $shallTrackInventorySetting): bool|null
//	{
//		if (!$shallTrackInventorySetting) {
//			return true;
//		}
//
//		if (!$this->commodityGroup || !$this->productProp) {
//			return null;
//		}
//
//		if ($this->commodityGroup->not_track_inventory) {
//			return true;
//		}
//
//		return $this->productProp->available_qty > 0;
//	}

	public function setNonVariantCharacteristics($characteristics): self
	{
		$this->nonVariantCharacteristics = $characteristics;
		return $this;
	}

	public function setExtendedVariants($variants): self
	{
		$this->extendedVariants = $variants;
		return $this;
	}

	public function setCompiledSeoProps($props): self
	{
		$this->compiledSeoProps = $props;
		return $this;
	}

	public function fields(): array
	{
		$out = ['product_id', 'sku'];
		if ($this->productTexts) {
			$out['title'] = fn () => $this->productTexts[0]->title;
			$out['url_key'] = fn () => $this->productTexts[0]->url_key;
		}
		$out = array_merge($out, ['has_variants', 'external_id']);

		if (isset($this->__item_id)) {
			$out['item_id'] = function () {
				return $this->__item_id;
			};
		}
		$out['in_stock'] = function () {
			return $this->productProp->available_qty > 0;
		};

		$out['prices'] = fn () => $this->inventoryItem?->finalPrices;

//		if (isset($this->__product_price)) {
//			$out['price'] = function () {
//				if (is_null($this->__product_price['currency_alias'])) {
//					return null;
//				} else {
//					return $this->__product_price;
//				}
//			};
//		}

		$out['text'] = function () {
			if ($this->productTexts) {
				return $this->productTexts[0];
			}
		};

		if ($this->isRelationPopulated('productImages')) {
			$out['images'] = function () {
				return $this->productImages;
			};
		}

		$out['props'] = function () {
			return $this->productProp;
		};

		if ($this->isRelationPopulated('manufacturer')) {
			$out['manufacturer'] = function () {
				return $this->manufacturer;
			};
		}

		if ($this->isRelationPopulated('categories')) {
			$out['categories'] = function () {
				return $this->categories;
			};
		}

		if ($this->isRelationPopulated('productCategoryRels')) {
			$out['categoryRels'] = function () {
				return $this->productCategoryRels;
			};
		}

		if ($this->isRelationPopulated('defaultCategory')) {
			$out['default_category'] = function () {
				return $this->defaultCategory;
			};
		}

		if ($this->isRelationPopulated('commodityGroup')) {
			$out['product_type'] = function () {
				return $this->commodityGroup;
			};
		}

		if ($this->isRelationPopulated('variants')) {
			$out['variants'] = function () {
				return $this->variants;
			};
		}

		if ($this->isRelationPopulated('labels')) {
			$out['labels'] = function () {
				return $this->labels;
			};
		}

		if (isset($this->extendedVariants)) {
			$out['extendedVariants'] = function (self $model) {
				return $model->extendedVariants;
			};
		}

		if (isset($this->nonVariantCharacteristics)) {
			$out['attributes'] = function (self $model) {
				return $model->nonVariantCharacteristics;
			};
		}

		if (isset($this->compiledSeoProps)) {
			$out['seo'] = function (self $model) {
				return $model->compiledSeoProps;
			};
		}

		$out = array_merge($out, ['status', 'created_by', 'created_at', 'deleted_at']);

		return $out;
	}
}
