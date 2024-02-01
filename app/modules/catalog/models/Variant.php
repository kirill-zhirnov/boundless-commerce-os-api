<?php

namespace app\modules\catalog\models;

use Yii;
use app\modules\system\models\Lang;
use app\modules\inventory\models\InventoryItem;
use app\modules\inventory\models\VwTrackInventory;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "variant".
 *
 * @property int $variant_id
 * @property int|null $product_id
 * @property string|null $sku
 * @property int|null $cases
 * @property string $created_at
 * @property string|null $deleted_at
 * @property string $size
 *
 * @property CharacteristicTypeCase[] $cases0
 * @property CharacteristicVariantVal[] $characteristicVariantVals
 * @property Characteristic[] $characteristics
 * @property InventoryItem $inventoryItem
 * @property Lang[] $langs
 * @property Product $product
 * @property ProductImportRel[] $productImportRels
 * @property VariantText[] $variantTexts
 * @property VariantText $variantTextDefault
 * @property VariantImage[] $variantImages
 * @property VwTrackInventory $vwTrackInventory
 */
class Variant extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'variant';
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
			[['product_id', 'cases'], 'default', 'value' => null],
			[['product_id', 'cases'], 'integer'],
			[['sku'], 'string'],
			[['created_at', 'deleted_at', 'size'], 'safe'],
			[['product_id', 'sku'], 'unique', 'targetAttribute' => ['product_id', 'sku']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'variant_id' => 'Variant ID',
			'product_id' => 'Product ID',
			'sku' => 'Sku',
			'cases' => 'Cases',
			'created_at' => 'Created At',
			'deleted_at' => 'Deleted At',
			'size' => 'Size',
		];
	}

	/**
	 * Gets query for [[Cases0]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCases0()
	{
		return $this->hasMany(CharacteristicTypeCase::class, ['case_id' => 'case_id'])->viaTable('characteristic_variant_val', ['variant_id' => 'variant_id']);
	}

	/**
	 * Gets query for [[CharacteristicVariantVals]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristicVariantVals()
	{
		return $this->hasMany(CharacteristicVariantVal::className(), ['variant_id' => 'variant_id']);
	}

	/**
	 * Gets query for [[Characteristics]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristics()
	{
		return $this->hasMany(Characteristic::class, ['characteristic_id' => 'characteristic_id'])->viaTable('characteristic_variant_val', ['variant_id' => 'variant_id']);
	}

	/**
	 * Gets query for [[InventoryItem]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInventoryItem()
	{
		return $this->hasOne(InventoryItem::class, ['variant_id' => 'variant_id']);
	}

	/**
	 * Gets query for [[Langs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLangs()
	{
		return $this->hasMany(Lang::class, ['lang_id' => 'lang_id'])->viaTable('variant_text', ['variant_id' => 'variant_id']);
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
	 * Gets query for [[ProductImportRels]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductImportRels()
	{
		return $this->hasMany(ProductImportRel::className(), ['variant_id' => 'variant_id']);
	}

	/**
	 * Gets query for [[VariantTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getVariantTexts()
	{
		return $this->hasMany(VariantText::class, ['variant_id' => 'variant_id']);
	}

	public function getVariantTextDefault()
	{
		return $this->hasOne(VariantText::class, ['variant_id' => 'variant_id'])
			->andWhere(['variant_text.lang_id' => Lang::DEFAULT_LANG])
		;
	}

	public function getVariantImages()
	{
		return $this->hasMany(VariantImage::class, ['variant_id' => 'variant_id']);
	}

	public static function loadVariantsForTpl($productId): array
	{
		$variants = Variant::find()
			->with(['variantTextDefault'])
			->with(['inventoryItem.finalPrices' => function(ActiveQuery $query) {
				FinalPrice::addFinalPricesSelect($query);
			}])
			->with(['inventoryItem.finalPrices.currency', 'inventoryItem.finalPrices.price'])
			->with(['inventoryItem.vwTrackInventory'])
			->where([
				'product_id' => $productId,
				'deleted_at' => null
			])
			->orderBy(['variant_id' => SORT_ASC])
			->all()
		;

		return ArrayHelper::merge([
			'list' => $variants
		], self::loadCharacteristics($productId));

//		$out = self::loadCharacteristics($productId);
//		$out['list'] = self::loadProductVariants($productId);

		return $out;
	}

//	public static function loadProductVariants($productId): array
//	{
//		$rows = self::getDb()->createCommand('
//			select
//				v.variant_id,
//				v.sku,
//				t.title,
//				p.value as price,
//				p.old as price_old,
//				i.item_id,
//				vw.track_inventory,
//				i.available_qty,
//				i.reserved_qty
//			from
//				variant v
//				inner join variant_text t on v.variant_id = t.variant_id and t.lang_id = :lang
//				inner join inventory_item i on i.variant_id = v.variant_id
//				inner join vw_track_inventory vw on vw.item_id = i.item_id
//				inner join product on product.product_id = v.product_id
//				left join (
//					select
//						f.item_id,
//						f.value,
//						f.old
//					from
//						final_price f
//						inner join price on price.price_id = f.price_id and price.alias = :price
//					where
//						f.point_id = :point
//				) p on p.item_id = i.item_id
//			where
//				v.product_id = :product
//				and v.deleted_at is null
//			order by
//				v.variant_id
//		')
//			->bindValues([
//				'lang' => Lang::DEFAULT_LANG,
//				'point' => PointSale::DEFAULT_POINT,
//				'product' => $productId,
//				'price' => Price::ALIAS_SELLING_PRICE
//			])
//			->queryAll()
//		;
//
//		foreach ($rows as &$row) {
//			$row['in_stock'] = $row['available_qty'] > 0;
//
//			if (!is_null($row['price'])) {
//				$row['price'] = floatval($row['price']);
//			}
//
//			if (!is_null($row['price_old'])) {
//				$row['price_old'] = floatval($row['price_old']);
//			}
//		}
//
//		return $rows;
//	}

	public static function loadCharacteristics($productId): array
	{
		$rows = self::getDb()->createCommand('
			select
				variant.variant_id,
				pvc.characteristic_id,
				characteristic_text.title as characteristic_title,
				characteristic_type_case.case_id,
				characteristic_type_case_text.title as case_title
			from
				product_variant_characteristic pvc
				inner join characteristic_variant_val cvv on pvc.characteristic_id = cvv.characteristic_id
				inner join variant on cvv.variant_id = variant.variant_id
				inner join characteristic_text on
					characteristic_text.characteristic_id = pvc.characteristic_id
					and characteristic_text.lang_id = :lang
				inner join characteristic_type_case on characteristic_type_case.case_id = cvv.case_id
				inner join characteristic_type_case_text on
					characteristic_type_case.case_id = characteristic_type_case_text.case_id
					and characteristic_type_case_text.lang_id = :lang
				inner join characteristic on characteristic.characteristic_id = pvc.characteristic_id
			where
				pvc.product_id = :product
				and pvc.rel_type = :relType
				and cvv.rel_type = :relType
				and variant.product_id = :product
				and variant.deleted_at is null
			order by
				pvc.sort asc,
				characteristic_type_case.sort asc
		')
			->bindValues([
				'product' => $productId,
				'relType' => 'variant',
				'lang' => Lang::DEFAULT_LANG
			])
			->queryAll()
		;

		$characteristics = [];
		$combinations = [];
		//we need a class instead of object to convert number keys to JSON as an object ({}), not an array ([]);
		$idCombinations = new \stdClass();
		$id2Key = [];
		$idCases = [];

		foreach ($rows as $row) {
			if (!isset($id2Key[$row['characteristic_id']])) {
				$newCharacteristic = [
					'id' => $row['characteristic_id'],
					'title' => $row['characteristic_title'],
					'cases' => []
				];

				$characteristics[] = $newCharacteristic;
				$id2Key[$row['characteristic_id']] = sizeof($characteristics) - 1;
			}

			$characteristicRow = &$characteristics[$id2Key[$row['characteristic_id']]];

			if (!isset($idCases[$row['case_id']])) {
				$characteristicRow['cases'][] = [
					'id' => $row['case_id'],
					'title' => $row['case_title']
				];
				$idCases[$row['case_id']] = true;
			}

			if (!isset($combinations[$row['variant_id']])) {
				$combinations[$row['variant_id']] = [];
				$idCombinations->{$row['variant_id']} = new \stdClass;
			}

			$characteristicKey = "{$row['characteristic_id']}-{$row['case_id']}";
			if (array_search($characteristicKey, $combinations[$row['variant_id']]) === false) {
				$combinations[$row['variant_id']][] = $characteristicKey;
			}

			if (!isset($idCombinations->{$row['variant_id']}->{$row['characteristic_id']})) {
				$idCombinations->{$row['variant_id']}->{$row['characteristic_id']} = $row['case_id'];
			}
		}

		return [
			'characteristics' => $characteristics,
			'combinations' => $combinations,
			'idCombinations' => $idCombinations,
		];
	}

	public function isInStock(): bool
	{
		return $this->inventoryItem->available_qty > 0;
	}

	public function extractSellingPrice(): array
	{
		$price = null;
		$priceOld = null;

		$sellingPrices = array_values(
			array_filter($this->inventoryItem?->finalPrices, fn (FinalPrice $finalPrice) => $finalPrice->price?->isSellingPrice())
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

		return ['price' => $price, 'priceOld' => $priceOld];
	}

	public function fields(): array
	{
		$out = parent::fields();

		$out['title'] = fn () => $this->variantTextDefault?->title;
		$out['prices'] = fn () => $this->inventoryItem?->finalPrices;
		$out['in_stock'] = fn () => $this->isInStock();
		$out['inventoryItem'] = fn () => $this->inventoryItem;
		$out['images'] = fn () => $this->variantImages;

		unset($out['deleted_at']);

		return $out;
	}
}
