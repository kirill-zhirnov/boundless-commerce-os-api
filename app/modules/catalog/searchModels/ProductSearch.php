<?php

namespace app\modules\catalog\searchModels;

use app\modules\catalog\models\Category;
use app\modules\catalog\models\Characteristic;
use app\modules\catalog\models\Collection;
use app\modules\catalog\models\PointSale;
use app\modules\catalog\models\Price;
use app\modules\catalog\models\Product;
use app\modules\catalog\models\VwProductList;
use app\modules\system\models\Lang;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\web\BadRequestHttpException;
use Yii;

class ProductSearch extends Model
{
	const MAX_CHARACTERISTICS_FILTER = 7;

	public $category;
	public $collection;
	public $product;
	public $attrs;
//	public $props;
	public $in_stock;
	public $price_min;
	public $price_max;
	public $brand;
	public $cross_sell_category;
	public $cross_sell_product;
	public $text_search;
	public $removed;
	public $published_status;

	/**
	 * @var Characteristic[]
	 */
	protected array $filterCharacteristics;

	/**
	 * Sort settings for DataProvider.
	 *
	 * @var array
	 */
	protected array $sort;

	protected ActiveQuery $query;

	public function rules(): array
	{
		return [
			[['category','product','brand'], 'each', 'rule' => ['integer']],
			[['collection'], 'validateCollection'],
			['in_stock', 'in', 'range' => ['0', '1']],
			[['price_min', 'price_max'], 'number', 'min' => 0],
			[['price_min', 'price_max'], 'filter', 'filter' => function ($value) {
				return floatval($value);
			}],
			['attrs', 'validateProps'],
			['cross_sell_category', 'in', 'range' => ['related', 'similar']],
			['cross_sell_product', 'number', 'min' => 0],
			['cross_sell_category', 'validateCrossSell'],
			['text_search', 'string', 'length' => [3, 20]],
			['text_search', 'trim'],
			['text_search', 'filter', 'filter' => 'mb_strtolower'],
            ['removed', 'in', 'range' => ['all', 'removed']],
            ['published_status', 'in', 'range' => ['all', 'hidden']]
		];
	}

	public function search(array $params = []): ActiveDataProvider
	{
		$this->load($params, '');
		if (!$this->validate()) {
			throw new BadRequestHttpException(implode(' ', $this->getErrorSummary(false)));
		}

		$this->sort = $this->getBasicSortConfig();
		$this->makeQuery();

		$totalCount = $this->calcTotalCount();
		$dataProvider = new ActiveDataProvider([
			'query' => $this->query,
			'totalCount' => $totalCount,
			'sort' => $this->sort,
			'pagination' => [
				'pageSizeLimit' => [1, 100]
			]
		]);

		return $dataProvider;
	}

	public function makeQuery()
	{
		$this->query = VwProductList::find();
		$this->query
			->alias('vw')
			->select(new Expression('distinct on ('.$this->getSqlDistinct().' ) vw.*'))
			->with(['finalPrices.currency', 'finalPrices.price'])
		;

		$this->applyGeneralFilters();
		$this->applyCharacteristicsFilter();

		return $this;
	}

	public function getQuery(): ActiveQuery
	{
		return $this->query;
	}

	protected function getBasicSortConfig(): array
	{
		return [
			'enableMultiSort' => true,
			'attributes' => [
				'price' => [
					'asc' => ['vw.sort_price' => SORT_ASC],
					'desc' => ['vw.sort_price' => SORT_DESC],
					'default' => SORT_ASC,
				],
				'title' => [
					'asc' => ['vw.title' => SORT_ASC],
					'desc' => ['vw.title' => SORT_DESC],
					'default' => SORT_ASC,
				],
				'in_stock' => [
					'asc' => ['vw.sort_in_stock' => SORT_ASC],
					'desc' => ['vw.sort_in_stock' => SORT_DESC],
					'default' => SORT_ASC,
				]
			],
			'defaultOrder' => [
				'price' => SORT_ASC
			],
		];
	}

	public function calcTotalCount(): int
	{
		$countQuery = clone $this->query;

		return (int) $countQuery
			->limit(-1)
			->offset(-1)
			->orderBy([])
			->count('distinct vw.product_id')
		;
	}

	protected function getSqlDistinct()
	{
		$distinct = ['vw.product_id', 'vw.title', 'vw.sort_price', 'vw.sort_in_stock'];
		if (!empty($this->category)) {
			$distinct[] = 'category_rel.sort';
		}
		if (!empty($this->collection)) {
			$distinct[] = 'collection_product_rel.sort';
		}

		if (!empty($this->cross_sell_category) && !empty($this->cross_sell_product)) {
			$distinct[] = 'cross_sell.sort';
		}

		return implode(',', $distinct);
	}

	public function validateProps()
	{
		if (!is_array($this->attrs)) {
			$this->addError('attrs', 'Attribute can be an object only');
			return;
		}

		$propKeys = array_keys($this->attrs);
		if (sizeof($propKeys) > self::MAX_CHARACTERISTICS_FILTER) {
			$this->addError('attrs', 'You can filter max by ' . self::MAX_CHARACTERISTICS_FILTER . ' on the same time.');
			return;
		}

		$validValues = [];
		$this->filterCharacteristics = $this->fetchFilterCharacteristics($propKeys);
		foreach ($this->attrs as $characteristicKey => $filterValue) {
			$filterResult = array_filter($this->filterCharacteristics,
				fn ($row) => (is_numeric($characteristicKey) && $row['characteristic_id'] == $characteristicKey) || $row['alias'] === $characteristicKey
			);
			$filterResult = array_values($filterResult);

			if (empty($filterResult)) {
				continue;
			}

			$characteristic = $filterResult[0];

			if ($characteristic->isMultiCase()) {
				if (is_array($filterValue)) {
					$validFilterValue = array_map('strval', array_filter($filterValue, 'is_numeric'));

					if (!empty($validFilterValue)) {
						$validValues[$characteristic->alias] = $validFilterValue;
					}
				}
			} else {
				$validFilterValue = is_string($filterValue) ? trim($filterValue) : '';

				if ($validFilterValue !== '') {
					$validValues[$characteristic->alias] = $validFilterValue;
				}
			}
		}

		$this->attrs = $validValues;
	}

	/**
	 * @param array $idList
	 * @return Characteristic[]
	 */
	protected function fetchFilterCharacteristics(array $characteristicKeys): array
	{
		$aliasList = [];
		$idList = [];
		foreach ($characteristicKeys as $key) {
			//for back compatibility:
			if (is_numeric($key)) {
				$idList[] = $key;
			} else {
				$aliasList[] = $key;
			}
		}

		/** @var Characteristic[] $rows */
		$rows = Characteristic::find()
			->leftJoin(
				"(
					select
						characteristic_id,
						json_agg(distinct case_id) as cases_in_variants
					from
						characteristic_variant_val
					where
						rel_type = 'variant'
					group by
						characteristic_id
				) ch_with_variants",
				'ch_with_variants.characteristic_id = characteristic.characteristic_id'
			)
			->select(['characteristic.*', 'ch_with_variants.cases_in_variants'])
			->where([
				'or',
				['characteristic.characteristic_id' => $idList],
				['characteristic.alias' => $aliasList]
			])
			->all()
		;

//		$out = [];
//		foreach ($rows as $row) {
//			$out[$row->characteristic_id] = $row;
//		}

		return $rows;
	}

	protected function applyGeneralFilters()
	{
		if (!empty($this->product)) {
			$this->query->andFilterWhere(['vw.product_id' => $this->product]);
		}

		if (!empty($this->category)) {
			$this->query->innerJoin('product_category_rel as category_rel', 'category_rel.product_id = vw.product_id');
			$this->query->innerJoin(
				'category',
				'category_rel.category_id = category.category_id
				and category.deleted_at is null
				and category.status = :categoryStatus
			',
				['categoryStatus' => Category::STATUS_PUBLISHED]
			);

			$this->query->andFilterWhere(['category.category_id' => $this->category]);
			$this->sort['attributes']['in_category'] = [
				'asc' => new Expression('category_rel.sort asc nulls last'),
				'desc' => new Expression('category_rel.sort desc nulls last'),
			];
			$this->sort['defaultOrder'] = [
				'in_category' => SORT_ASC
			];
		}

		if (!empty($this->collection)) {
			$this->query->innerJoin(
				'collection_product_rel',
				'collection_product_rel.product_id = vw.product_id'
			);

			$this->query->andFilterWhere(['collection_product_rel.collection_id' => $this->collection]);
			$this->sort['attributes']['in_collection'] = [
				'asc' => new Expression('collection_product_rel.sort asc nulls last'),
				'desc' => new Expression('collection_product_rel.sort desc nulls last'),
			];
			$this->sort['defaultOrder'] = [
				'in_collection' => SORT_ASC
			];
		}

		if (!empty($this->cross_sell_category) && !empty($this->cross_sell_product)) {
			$this->query->innerJoin('cross_sell', 'cross_sell.rel_product_id = vw.product_id');
			$this->query->innerJoin('cross_sell_category', 'cross_sell.category_id = cross_sell_category.category_id');
			$this->query->andWhere([
				'cross_sell.product_id' => $this->cross_sell_product,
				'cross_sell_category.alias' => $this->cross_sell_category
			]);
			$this->sort['attributes']['cross_sell'] = [
				'asc' => new Expression('cross_sell.sort asc nulls last'),
				'desc' => new Expression('cross_sell.sort desc nulls last'),
			];
			$this->sort['defaultOrder'] = [
				'cross_sell' => SORT_ASC
			];
		}

		if (isset($this->in_stock)) {
			$this->query->innerJoin('product_prop', 'product_prop.product_id = vw.product_id');

			if ($this->in_stock == '1') {
				$this->query->andWhere('product_prop.available_qty > 0');
			} elseif ($this->in_stock == '0') {
				$this->query->andWhere('product_prop.available_qty = 0');
			}
		}

		if ($this->price_min) {
			$this->query->andWhere('
				exists (
					select
						1
					from
						final_price
						inner join price using(price_id)
					where
						final_price.point_id = :pointId
						and price.alias = :sellingPrice
						and final_price.item_id = vw.item_id
						and (
							final_price.value >= :minFinalPrice
							or final_price.min >= :minFinalPrice
							or final_price.max >= :minFinalPrice
						)
				)
			', [
				'pointId' => PointSale::DEFAULT_POINT,
				'sellingPrice' => Price::ALIAS_SELLING_PRICE,
				'minFinalPrice' => $this->price_min
			]);
		}

		if ($this->price_max) {
			$this->query->andWhere('
				exists (
					select
						1
					from
						final_price
						inner join price using(price_id)
					where
						final_price.point_id = :pointId
						and price.alias = :sellingPrice
						and final_price.item_id = vw.item_id
						and (
							final_price.value <= :maxFinalPrice or final_price.max <= :maxFinalPrice
						)
				)
			', [
				'pointId' => PointSale::DEFAULT_POINT,
				'sellingPrice' => Price::ALIAS_SELLING_PRICE,
				'maxFinalPrice' => $this->price_max
			]);
		}

		$this->query->andFilterWhere(['vw.manufacturer_id' => $this->brand]);

		if ($this->text_search) {
			$this->query
				->innerJoin(
					'product_text',
					'product_text.product_id = vw.product_id and product_text.lang_id=' . Lang::DEFAULT_LANG
				)
				->andWhere('
					lower(vw.sku) like :textSearch
					or lower(vw.title) like :textSearch
					or lower(product_text.description) like :textSearch
				', ['textSearch' => '%' . $this->text_search . '%'])
			;
		}

        if (empty($this->removed)) {
            $this->query->andWhere('vw.deleted_at is null');
        } else if ($this->removed === 'removed') {
            $this->query->andWhere('vw.deleted_at is not null');
        }

        if (empty($this->published_status)) {
            $this->query->andWhere('vw.status = :status', ['status' => Product::STATUS_PUBLISHED]);
        } else if ($this->published_status === 'hidden') {
            $this->query->andWhere('vw.status = :status', ['status' => Product::STATUS_HIDDEN]);
        }
	}

	protected function applyCharacteristicsFilter()
	{
		if (!is_array($this->attrs)) {
			return;
		}

		$db = Yii::$app->get('instanceDb');
		$filterByTextVals = [];
		foreach ($this->filterCharacteristics as $characteristic) {
			if (!isset($this->attrs[$characteristic->alias])) {
				continue;
			}

			$value = $this->attrs[$characteristic->alias];

			if ($characteristic->isMultiCase()) {
				$sqlParts = array_map(function ($caseId) use ($db, $characteristic) {
					return "characteristic->'" . $characteristic->alias ."' @> " . $db->quoteValue($caseId);
				}, $value);

				$this->query->andWhere('
					exists (
						select 1
						from
							product_prop
						where
							product_prop.product_id = vw.product_id
							and ' . implode(' or ', $sqlParts) . '
					)
				');

				//filter by variants that are in_stock only
				if ($this->in_stock == '1' && !empty($characteristic->cases_in_variants)) {
					$valueInCases = array_filter($value, function ($caseId) use ($characteristic) {
						return in_array($caseId, $characteristic->cases_in_variants);
					});

					if (!empty($valueInCases)) {
						$casesWhere = array_map(function ($caseId) use ($db) {
							return $db->quoteValue($caseId) . " = any(variant.cases)";
						}, $valueInCases);
						$this->query->andWhere('
							exists (
								select 1
								from
									variant
									inner join inventory_item using(variant_id)
									inner join vw_track_inventory using(item_id)
								where
									variant.product_id = vw.product_id
									and variant.deleted_at is null
									and (' . implode(' or ', $casesWhere) . ')
									and (
										(vw_track_inventory.track_inventory is true and inventory_item.available_qty > 0)
										or
										(vw_track_inventory.track_inventory is false)
									)
							)
						');
					}
				}
			} else {
				$filterByTextVals[] = $value;
			}
		}

		if ($filterByTextVals) {
			$quotedTextVals = array_map(function ($textVal) use ($db) {
				return "characteristic_product_val_text.value like ". $db->quoteValue('%' . $textVal . '%');
			}, $filterByTextVals);

			$this->query->andWhere('
				exists (
					select 1
					from
						characteristic_product_val
						inner join characteristic_product_val_text using(value_id)
					where
						characteristic_product_val.product_id = vw.product_id
						and lang_id = ' . Lang::DEFAULT_LANG . '
						and (' . implode(' and ', $quotedTextVals) . ')
				)
			');
		}
	}

	public function validateCollection()
	{
		$collectionIds = [];
		if (is_array($this->collection)) {
			foreach ($this->collection as $item) {
				if (is_numeric($item)) {
					$collectionIds[] = intval($item);
				} else {
					/** @var Collection $row */
					$row = Collection::find()->where(['alias' => $item])->one();
					if ($row) {
						$collectionIds[] = $row->collection_id;
					}
				}
			}
		}

		$this->collection = $collectionIds;
	}

	public function validateCrossSell()
	{
		if (!empty($this->cross_sell_product) && trim($this->cross_sell_category) === '') {
			$this->addError('cross_sell_category', '"cross_sell_category" must be specified if the cross_sell_product is passed.');
			return;
		}

		if (!empty($this->cross_sell_category) && empty($this->cross_sell_product)) {
			$this->addError('cross_sell_product', '"cross_sell_product" must be specified if the cross_sell_category is passed.');
			return;
		}
	}
}
