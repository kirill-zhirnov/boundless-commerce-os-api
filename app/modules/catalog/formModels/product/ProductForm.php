<?php

namespace app\modules\catalog\formModels\product;

use app\components\InstancesQueue;
use app\modules\catalog\models\CollectionProductRel;
use app\modules\catalog\models\CommodityGroup;
use app\modules\catalog\models\Manufacturer;
use app\modules\catalog\models\Price;
use app\modules\catalog\models\Product;
use app\modules\catalog\models\ProductCategoryRel;
use app\modules\catalog\models\ProductLabelRel;
use app\modules\catalog\models\ProductProp;
use app\modules\catalog\models\ProductText;
use app\modules\catalog\models\TaxClass;
use app\modules\catalog\validators\ProductCategoriesValidator;
use app\modules\catalog\validators\ProductCollectionsValidator;
use app\modules\catalog\validators\ProductDimensionsValidator;
use app\modules\catalog\validators\ProductLabelsValidator;
use app\modules\catalog\validators\ProductPricesValidator;
use app\modules\catalog\validators\StockPerWarehouseValidator;
use app\modules\inventory\models\InventoryItem;
use app\modules\inventory\models\InventoryMovement;
use app\modules\inventory\models\InventoryOption;
use app\modules\inventory\models\Warehouse;
use app\modules\system\models\Lang;
use app\modules\system\models\Setting;
use app\validators\UrlKeyValidator;
use yii\base\Model;
use yii\db\Expression;
use yii\db\Query;
use Yii;

class ProductForm extends Model
{
	public $title;
	public $url_key;
	public $description;
	public $sku;
	public $manufacturer_id;
	public $group_id;
	public $external_id;
	public $publishing_status;
	public $prices;
	public $categories;
	public $labels;
	public $collections;
	public $dimensions;
	public $is_in_stock;
	public $stock_per_warehouse;
	public $tax_status;
	public $tax_class_id;
	public $arbitrary_data;

	protected ?Product $product;

	public function rules(): array
	{
		return array_merge(
			$this->getBasicRules(),
			[
				[['title'], 'required'],
				[['title'], 'trim'],
				[
					'group_id',
					'default',
					'value' => function () {
						/** @var CommodityGroup $defaultGroup */
						$defaultGroup = CommodityGroup::find()->where(['is_default' => true])->one();
						return $defaultGroup?->group_id;
					},
					'on' => 'create'
				],
				[
					'stock_per_warehouse',
					StockPerWarehouseValidator::class,
					'groupIdAttribute' => 'group_id',
					'isInStockAttribute' => 'is_in_stock',
					'skipOnEmpty' => false,
					'on' => 'create',
				],
				[
					'prices',
					ProductPricesValidator::class,
					'on' => 'create'
				],
			]
		);
	}

	public function save(): bool
	{
		if (!$this->validate()) {
			return false;
		}

		if (!isset($this->product)) {
			$this->product = new Product();
		}

		$isNew = $this->product->isNewRecord;
		$prevGroupId = $isNew ? null : $this->product->group_id;

		$this->product->attributes = [
			'sku' => $this->sku,
			'manufacturer_id' => $this->manufacturer_id,
			'group_id' => $this->group_id,
			'external_id' => $this->external_id,
			'status' => $this->publishing_status ?? Product::STATUS_PUBLISHED
		];
		$this->product->save(false);

		if (!$isNew && $this->group_id != $prevGroupId) {
			$this->product->onGroupChanged($prevGroupId, $this->group_id);
		}

		$productText = $this->product->productTextDefault;
		$productText->attributes = [
			'title' => $this->title,
			'url_key' => $this->url_key ?? Product::findUniqueUrlKeyByTitle($this->title, $this->product->product_id),
			'description' => $this->description
		];
		$productText->save(false);

		$productProp = $this->product->productProp;
		$productProp->attributes = [
			'size' => $this->dimensions ?? new Expression('DEFAULT'),
			'tax_status' => $this->tax_status ?? ProductProp::TAX_STATUS_TAXABLE,
			'tax_class_id' => $this->tax_class_id,
			'arbitrary_data' => $this->arbitrary_data
		];
		$productProp->save(false);

		$this->saveCategories();
		$this->saveLabels();
		$this->saveCollections();
		$this->savePrices();
		$this->saveStock();

		/** @var InstancesQueue $queue */
		$queue = Yii::$app->queue;
		if ($isNew) {
			$queue->modelCreated(Product::class, [$this->product->product_id]);
		} else {
			$queue->modelUpdated(Product::class, [$this->product->product_id]);
		}

		return true;
	}

	protected function saveStock()
	{
		if (!isset($this->is_in_stock) && !isset($this->stock_per_warehouse)) {
			return;
		}

		$shallTrackInventory = Setting::shallTrackInventory();
		$group = CommodityGroup::findOne($this->product->group_id);
		if ($group->not_track_inventory || !$shallTrackInventory) {
			if (isset($this->is_in_stock)) {
				$qty = $this->is_in_stock ? 1 : 0;
				InventoryItem::updateItemsQty([$this->product->inventoryItem->item_id], $qty);
			}
		} else {
			if (isset($this->stock_per_warehouse)) {
				$movement = InventoryMovement::createByReason(
					InventoryOption::CATEGORY_SYSTEM_CHANGE_QTY,
					InventoryOption::ALIAS_API_REQUEST
				);
				$this->saveStockPerLocation($movement);
				$movement->destroyIfEmpty();
			}
		}
	}

	protected function saveStockPerLocation(InventoryMovement $movement)
	{
		/** @var Warehouse[] $warehouses */
		$warehouses = Warehouse::find()->where(['deleted_at' => null])->orderBy(['sort' => SORT_ASC])->all();
		$warehouseByKeys = [];
		foreach ($warehouses as $warehouse) {
			$warehouseByKeys[$warehouse->warehouse_id] = $warehouse;
		}

		$qtyPerWarehouse = [];
		if (isset($this->stock_per_warehouse['total'])) {
			foreach ($warehouses as $i => $warehouse) {
				if ($i == 0) {
					$qtyPerWarehouse[$warehouse->warehouse_id] = $this->stock_per_warehouse['total'];
				} else {
					$qtyPerWarehouse[$warehouse->warehouse_id] = 0;
				}
			}
		} else {
			$qtyPerWarehouse = $this->stock_per_warehouse;
		}

		foreach ($qtyPerWarehouse as $warehouseId => $qty) {
			$warehouse = $warehouseByKeys[$warehouseId];

			$this->product->inventoryItem
				->changeAvailableQty($warehouse->inventoryLocation, $qty, $movement)
			;
		}
	}

	protected function savePrices()
	{
		if (is_array($this->prices)) {
			$currency = Setting::getCurrency();
			foreach ($this->prices as $priceAlias => $value) {
				$priceRow = Price::findOne(['alias' => $priceAlias]);

				$priceValue = $value['price'];
				$compareAt = $value['compareAtPrice'] ?? null;
				$this->product->inventoryItem->setPrice($priceRow, $currency, $priceValue, $compareAt);
			}
		}
	}

	protected function saveCollections()
	{
		if (is_array($this->collections)) {
			$collectionIDs = array_map(fn ($row) => $row['collection_id'], $this->collections);
			CollectionProductRel::setCollectionRels($this->product->product_id, $collectionIDs);
		}
	}

	protected function saveLabels()
	{
		if (is_array($this->labels)) {
			$labelIDs = array_map(fn ($row) => $row['label_id'], $this->labels);
			ProductLabelRel::setLabelRels($this->product->product_id, $labelIDs);
		}
	}

	protected function saveCategories()
	{
		if (is_array($this->categories)) {
			$assignToCategories = [];
			$defaultCategoryId = null;
			foreach ($this->categories as $row) {
				$categoryId = intval($row['category_id']);
				$assignToCategories[] = $categoryId;
				if (!empty($row['is_default'])) {
					$defaultCategoryId = $categoryId;
				}
			}

			ProductCategoryRel::setProductCategories($this->product->product_id, $assignToCategories);
			if ($defaultCategoryId) {
				ProductCategoryRel::assignDefaultCategory($this->product->product_id, $defaultCategoryId);
			}
		}
	}

	public function getProduct(): ?Product
	{
		return $this->product;
	}

	public function setProduct(?Product $product): self
	{
		$this->product = $product;
		return $this;
	}

	public function validateArbitraryData()
	{
		if (isset($this->arbitrary_data) && !is_array($this->arbitrary_data)) {
			$this->addError('arbitrary_data', Yii::t('app', 'Arbitrary data should be a key-value object.'));
			return;
		}
	}

	public function getBasicRules(): array
	{
		return [
			[['title', 'url_key'], 'string', 'max' => 1000],
			[['url_key'], UrlKeyValidator::class],
			[
				['url_key'],
				'unique',
				'targetClass' => ProductText::class,
				'targetAttribute' => 'url_key',
				'filter' => function(Query $query) {
					$query->andWhere(['lang_id' => Lang::DEFAULT_LANG]);

					if (isset($this->product) && !$this->product->isNewRecord) {
						$query->andWhere('product_text.product_id != :productId', [
							'productId' => $this->product->product_id
						]);
					}
				}
			],
			[['description'], 'string', 'max' => 66000],
			[['sku'], 'string', 'max' => 1000],
			[
				['sku'],
				'unique',
				'targetClass' => Product::class,
				'targetAttribute' => 'sku',
				'filter' => function(Query $query) {
					if (isset($this->product) && !$this->product->isNewRecord) {
						$query->andWhere('product.product_id != :productId', [
							'productId' => $this->product->product_id
						]);
					}
				}
			],
			[['manufacturer_id', 'group_id'], 'integer', 'min' => 0],
			[
				'manufacturer_id',
				'exist',
				'targetClass' => Manufacturer::class,
				'targetAttribute' => 'manufacturer_id'
			],
			[
				'group_id',
				'exist',
				'targetClass' => CommodityGroup::class,
				'targetAttribute' => 'group_id'
			],
			['external_id', 'string', 'max' => 1000],
			[
				'external_id',
				'unique',
				'targetClass' => Product::class,
				'targetAttribute' => 'external_id',
				'filter' => function(Query $query) {
					if (isset($this->product) && !$this->product->isNewRecord) {
						$query->andWhere('product.product_id != :productId', [
							'productId' => $this->product->product_id
						]);
					}
				}
			],
			[
				'publishing_status',
				'in',
				'range' => [Product::STATUS_PUBLISHED, Product::STATUS_HIDDEN]
			],
			[
				'tax_status',
				'in',
				'range' => [ProductProp::TAX_STATUS_NONE, ProductProp::TAX_STATUS_TAXABLE]
			],
			['tax_class_id', 'integer', 'min' => 0],
			[
				'tax_class_id',
				'exist',
				'targetClass' => TaxClass::class,
				'targetAttribute' => 'tax_class_id'
			],
			['categories', ProductCategoriesValidator::class],
			['labels', ProductLabelsValidator::class],
			['collections', ProductCollectionsValidator::class],
			['dimensions', ProductDimensionsValidator::class],
			['is_in_stock', 'safe'],
			['arbitrary_data', 'validateArbitraryData']
		];
	}
}
