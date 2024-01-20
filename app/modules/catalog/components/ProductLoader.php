<?php

namespace app\modules\catalog\components;

use app\modules\catalog\activeQueries\ProductQuery;
use app\modules\catalog\models\Category;
use app\modules\catalog\models\Characteristic;
use app\modules\catalog\models\FinalPrice;
use app\modules\catalog\models\Label;
use app\modules\catalog\models\PointSale;
use app\modules\catalog\models\Price;
use app\modules\catalog\models\Product;
use app\modules\catalog\models\Variant;
use app\modules\catalog\models\VwCharacteristicGrid;
use app\modules\system\models\Lang;
use app\modules\system\models\Setting;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;
use Yii;
use yii\web\NotFoundHttpException;

class ProductLoader
{
	protected Product|null $product;

	protected $extendedVariants;

	protected $removed;

	protected $published_status;

	public function __construct(protected $id)
	{
	}

	public function load(): Product
	{
		$query = $this->makeBasicProductQuery();

		if (is_numeric($this->id)) {
			$query->andWhere(['product.product_id' => $this->id]);
		} else {
			$query->andWhere(['product_text.url_key' => $this->id]);
		}

		$this->product = $query->one();
		if (!$this->product) {
			throw new NotFoundHttpException('Product not found');
		}

//		$this->product->__item_id = $this->product->inventoryItem->item_id;

		if ($this->product->has_variants) {
			$this->fetchVariants();
		}

		$this->fetchNonVariantsCharacteristics();
		$this->makeSeoProps();

		return $this->product;
	}

	public function makeBasicProductQuery(): ProductQuery
	{
		$query = Product::find();
		$query
			->addSelect('product.*')
			->innerJoinWith(['productTexts' => function (ActiveQuery $query) {
				$query->where(['product_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->addInventoryItemSelect()
//			->addProductPriceSelect()
			->addProductImagesSelect()
			->with(['productProp'])
			->withFinalPrices()
			->with(['manufacturer' => function (ActiveQuery $query) {
				$query->where(['manufacturer.deleted_at' => null]);
			}])
			->with(['manufacturer.manufacturerTexts' => function (ActiveQuery $query) {
				$query->where(['manufacturer_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->with(['manufacturer.image'])
			->with(['productCategoryRels' => function (ActiveQuery $query) {
				$query->orderBy(['product_category_rel.sort' => SORT_ASC]);
			}])
			->with(['productCategoryRels.category' => function (ActiveQuery $query) {
				$query->where([
					'category.status' => Category::STATUS_PUBLISHED,
					'category.deleted_at' => null
				]);
			}])
			->with(['productCategoryRels.category.categoryTexts' => function (ActiveQuery $query) {
				$query->where(['category_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->with(['productCategoryRels.category.categoryProp'])
			->with(['commodityGroup.commodityGroupTexts' => function (ActiveQuery $query) {
				$query->where(['commodity_group_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->with(['labels' => function (ActiveQuery $query) {
				$query
					->where('label.deleted_at is null')
					->orderBy(['label.label_id' => SORT_ASC])
				;
			}])
			->with(['labels.labelTexts' => function (ActiveQuery $query) {
				$query->where(['label_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
		;

		if (empty($this->removed)) {
			$query->andWhere('product.deleted_at is null');
		} else if ($this->removed === 'removed') {
			$query->andWhere('product.deleted_at is not null');
		}

		if (empty($this->published_status)) {
			$query->andWhere('product.status = :status', ['status' => Product::STATUS_PUBLISHED]);
		} else if ($this->published_status === 'hidden') {
			$query->andWhere('product.status = :status', ['status' => Product::STATUS_HIDDEN]);
		}

		return $query;
	}

	protected function makeSeoProps()
	{
		$variables = $this->getVariablesForSEOTemplates();
		$seoTemplates = Setting::getSeoTemplates();

		$seo = [
			'compiledTitle' => null,
			'compiledMetaDescription' => null,
			'customTitle' => $this->product->productTexts[0]->custom_title,
			'customMetaDesc' => $this->product->productTexts[0]->meta_description,
		];

		$m = new \Mustache_Engine(['entity_flags' => ENT_QUOTES]);
		if (isset($seoTemplates['product'])) {
			$seo['compiledTitle'] = $m->render($seoTemplates['product']['title'], $variables);
			$seo['compiledMetaDescription'] = $m->render($seoTemplates['product']['metaDescription'], $variables);
		}

		$seo['title'] = $seo['customTitle'] ?: $seo['compiledTitle'];
		$seo['metaDesc'] = $seo['customMetaDesc'] ?: $seo['compiledMetaDescription'];

		$this->product->setCompiledSeoProps($seo);
	}

	protected function getVariablesForSEOTemplates(): array
	{
		$price = '';
		$priceOld = '';
		$currency = Setting::getCurrencyAlias();
		/** @var \yii\i18n\Formatter $formatter */
		$formatter = Yii::$app->formatter;

		if ($this->product->inventoryItem?->finalPrices) {
			$sellingPrices = array_values(
				array_filter($this->product->inventoryItem?->finalPrices, fn (FinalPrice $finalPrice) => $finalPrice->price?->isSellingPrice())
			);

			if ($sellingPrices) {
				$sellingPrice = $sellingPrices[0];

				if ($sellingPrice->min) {
					$price = $sellingPrice->min;
				} else if ($sellingPrice->value) {
					$price = $sellingPrice->value;
				}

				if ($sellingPrice->old_min) {
					$priceOld = $sellingPrice->old_min;
				} else if ($sellingPrice->old) {
					$priceOld = $sellingPrice->old;
				}
			}
		}

		$out = [
			'id' => $this->product->product_id,
			'title' => $this->product->productTexts[0]->title,
			'sku' => $this->product->sku,
			'skuVariants' => $this->extendedVariants
				? implode(', ', array_reduce($this->extendedVariants['list'], function(array $out, $variant) {
						if ($variant['sku']) {
							$out[] = $variant['sku'];
						}

						return $out;
					}, []))
				: '',
			'description' => $this->product->productTexts[0]->getDescriptionAsText(),
			'shortDescription' => $this->product->productTexts[0]->getShortDescription(),
			'category' => $this->product->defaultCategory?->categoryTexts[0]->title,
			'inStock' => $this->product->productProp->available_qty > 0,
			'hasVariants' => $this->product->has_variants,
			'manufacturer' => $this->product->manufacturer?->manufacturerTexts[0]->title,
			'price' => $price ? $formatter->asCurrency($price, $currency) : '',
			'priceOld' => $priceOld ? $formatter->asCurrency($priceOld, $currency) : '',
			'labels' => implode(', ', array_reduce($this->product->labels, function(array $out, Label $label) {
				$out[] = $label->labelTexts[0]->title;
				return $out;
			}, [])),
			'variants' => []
		];

		if (isset($this->extendedVariants, $this->extendedVariants['list'])) {
			/** @var Variant $row */
			foreach ($this->extendedVariants['list'] as $row) {
				$variantPrices = $row->extractSellingPrice();

				$out['variants'][] = [
					'title' => $row->variantTextDefault->title,
					'sku' => $row->sku,
					'price' => $variantPrices['price'] ? $formatter->asCurrency($variantPrices['price'], $currency) : '',
					'priceOld' => $variantPrices['priceOld'] ? $formatter->asCurrency($variantPrices['priceOld'], $currency) : '',
					'inStock' => $row->isInStock()
				];
			}
		}

		return $out;
	}

	protected function fetchVariants()
	{
		$this->extendedVariants = Variant::loadVariantsForTpl($this->product->product_id);
		$this->product->setExtendedVariants($this->extendedVariants);
	}

	protected function fetchNonVariantsCharacteristics()
	{
		$excludeCharacteristics = [];
		if ($this->product->has_variants) {
			$excludeCharacteristics = array_map(function ($item) {
				return $item['id'];
			}, $this->extendedVariants['characteristics']);
		}

		$this->product->setNonVariantCharacteristics(
			Characteristic::loadProductCharacteristic($this->product->productProp->characteristic, $excludeCharacteristics)
		);
	}

    public function setRemoved(string|null $removed): ProductLoader
    {
        $this->removed = $removed;
        return $this;
    }

    public function setPublishedStatus(string|null $publishedStatus): ProductLoader
    {
        $this->published_status = $publishedStatus;
        return $this;
    }
}
