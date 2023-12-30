<?php

namespace app\modules\catalog\searchModels;

use app\modules\catalog\models\Characteristic;
use app\modules\catalog\models\Manufacturer;
use app\modules\catalog\models\PointSale;
use app\modules\catalog\models\Price;
use app\modules\system\models\Lang;
use http\Exception\RuntimeException;
use yii\base\Model;
use yii\db\ActiveQuery;
use yii\db\Expression;

class FilterFieldsSearch extends Model
{
	const TYPE_PRICE = 'price';
	const TYPE_CHARACTERISTIC = 'characteristic';
	const TYPE_BRAND = 'brand';
	const TYPE_AVAILABILITY = 'availability';

	public $filter_fields;

	public $values;

	protected array $filters = [];

	public function rules(): array
	{
		return [
			[['filter_fields'], 'required'],
			[['filter_fields'], 'validateFields'],
			[['values'], 'validateValues'],
			[['values'], 'default', 'value' => []]
		];
	}

	public function initInputData(array $data): bool
	{
		$this->load($data, '');
		if ($this->validate()) {
			return true;
		} else {
			return false;
		}
	}

	public function makeFields(): array
	{
		if (!is_array($this->filter_fields)) {
			throw new RuntimeException('Input data is not inited');
		}

		$this->fetchFiltersRanges();

		//calc product total
		$productSearch = new ProductSearch();
		$productDataProvider = $productSearch->search($this->values);

		return [
			'ranges' => $this->filters,
			'totalProducts' => $productDataProvider->getTotalCount()
		];
	}

	public function validateFields()
	{
		$characteristicIds = [];
		$allowedTypes = [self::TYPE_PRICE, self::TYPE_CHARACTERISTIC, self::TYPE_BRAND, self::TYPE_AVAILABILITY];
		foreach ($this->filter_fields as $i => $field) {
			if (!is_numeric($i)) {
				$this->addError('filter_fields', 'Fields should be an array.');
				return;
			}

			if (!isset($field['type'])) {
				$this->addError('filter_fields[' . $i .']', 'Each field should be an object with type property');
				return;
			}

			if (!in_array($field['type'], $allowedTypes)) {
				continue;
			}

			if ($field['type'] == self::TYPE_CHARACTERISTIC && (!isset($field['characteristic_id']) || !is_numeric($field['characteristic_id']))) {
				$this->addError('filter_fields[' . $i .']', 'characteristic_id should be passed for the characteristic');
				return;
			}

			switch ($field['type']) {
				case self::TYPE_CHARACTERISTIC:
					$this->filters[] = [
						'type' => self::TYPE_CHARACTERISTIC,
						'characteristic_id' => intval($field['characteristic_id'])
					];
					$characteristicIds[] = $field['characteristic_id'];
					break;
				default:
					$this->filters[] = [
						'type' => $field['type'],
					];
					break;
			}
		}

		if (sizeof($characteristicIds) > ProductSearch::MAX_CHARACTERISTICS_FILTER) {
			$this->addError('filter_fields', 'Limit - ' . ProductSearch::MAX_CHARACTERISTICS_FILTER . ' characteristics reached.');
			return;
		}

		if ($characteristicIds) {
			$rows = Characteristic::find()
				->with(['characteristicTexts' => function (ActiveQuery $query) {
					$query->where(['characteristic_text.lang_id' => Lang::DEFAULT_LANG]);
				}])
				->with(['characteristicTypeCases' => function (ActiveQuery $query) {
					$query->orderBy(['characteristic_type_case.sort' => SORT_ASC]);
				}])
				->with(['characteristicTypeCases.characteristicTypeCaseTexts' => function (ActiveQuery $query) {
					$query->where(['characteristic_type_case_text.lang_id' => Lang::DEFAULT_LANG]);
				}])
				->where(['characteristic_id' => $characteristicIds])
				->all()
			;

			$characteristics = [];
			/** @var Characteristic $row */
			foreach ($rows as $row) {
				$characteristics[$row->characteristic_id] = $row;
			}

			foreach ($this->filters as $i => $filter) {
				if ($filter['type'] !== self::TYPE_CHARACTERISTIC) {
					continue;
				}

				if (!isset($characteristics[$filter['characteristic_id']])) {
					$this->addError('filter_fields', 'Cant find characteristic with ID:' . $filter['characteristic_id']);
					return;
				}

				$this->filters[$i]['characteristic'] = $characteristics[$filter['characteristic_id']];
			}
		}
	}

	public function validateValues()
	{
		if (!is_array($this->values)) {
			$this->addError('values', 'Values should be an object with filter values.');
			return;
		}

		$productSearch = new ProductSearch();
		$productSearch->load($this->values, '');
		if (!$productSearch->validate()) {
			foreach ($productSearch->getFirstErrors() as $field => $error) {
				$this->addError('values[' . $field . ']', $error);
			}
			return;
		}
	}

	protected function fetchFiltersRanges()
	{
		foreach ($this->filters as $i => $filter) {
			switch ($filter['type']) {
				case self::TYPE_PRICE:
					$range = $this->countPriceRange();

					if ($range) {
						$this->filters[$i]['range'] = $range;
					}
					break;

				case self::TYPE_CHARACTERISTIC:
					/** @var Characteristic $characteristic */
					$characteristic = $filter['characteristic'];
					if ($characteristic->isMultiCase()) {
						$productsByCases = $this->calcProductsByCharacteristicCases($characteristic);

						if ($productsByCases !== null) {
							foreach ($characteristic->characteristicTypeCases as $case) {
								$case->products_qty = $productsByCases[$case->case_id] ?? 0;
							}
						}
					}
					break;

				case self::TYPE_BRAND:
					$availableManufacturers = $this->fetchAvailableManufacturers();

					if ($availableManufacturers !== null) {
						$this->filters[$i]['manufacturers'] = $availableManufacturers;
					}
					break;
			}
		}
	}

	protected function calcProductsByCharacteristicCases(Characteristic $characteristic): array|null
	{
		$values = $this->values;
		if (isset($values['props']) && is_array($values['props'])) {
			$values['props'] = $this->getValuesWithout($values['props'], [$characteristic->characteristic_id]);
		}

		$productSearch = new ProductSearch();
		$productSearch->load($values, '');
		if (!$productSearch->validate()) {
			return null;
		}

		$rows = $productSearch->makeQuery()->getQuery()
			->limit(-1)
			->offset(-1)
			->orderBy([])
			->innerJoin(
				'characteristic_product_val',
				'characteristic_product_val.product_id = vw.product_id'
			)
			->andWhere(['characteristic_product_val.characteristic_id' => $characteristic->characteristic_id])
			->andWhere('characteristic_product_val.case_id is not null')
			->select([
				'characteristic_product_val.case_id',
				new Expression('count(distinct vw.product_id) as products_qty')
			])
			->groupBy('characteristic_product_val.case_id')
			->asArray()
			->all()
		;

		$out = [];
		foreach ($rows as $row) {
			$out[$row['case_id']] = $row['products_qty'];
		}

		return $out;
	}

	protected function fetchAvailableManufacturers(): array|null
	{
		$values = $this->getValuesWithout($this->values, ['brand']);

		$productSearch = new ProductSearch();
		$productSearch->load($values, '');
		if (!$productSearch->validate()) {
			return null;
		}

		$calcRows = $productSearch->makeQuery()->getQuery()
			->limit(-1)
			->offset(-1)
			->orderBy([])
			->innerJoin(
				'manufacturer',
				'manufacturer.manufacturer_id = vw.manufacturer_id'
			)
			->innerJoin(
				'manufacturer_text',
				'manufacturer.manufacturer_id = manufacturer_text.manufacturer_id and manufacturer_text.lang_id = :brandLangId',
				['brandLangId' => Lang::DEFAULT_LANG]
			)
			->andWhere('manufacturer.status = :manufacturerStatus and manufacturer.deleted_at is null', [
				'manufacturerStatus' => Manufacturer::STATUS_PUBLISHED
			])
			->select([
				'manufacturer.manufacturer_id',
				new Expression('count(distinct vw.product_id) as products_qty')
			])
			->groupBy(['manufacturer.manufacturer_id'])
			->asArray()
			->all()
		;

		$manufactureQtyRows = [];
		foreach ($calcRows as $row) {
			$manufactureQtyRows[$row['manufacturer_id']] = $row['products_qty'];
		}

		/** @var Manufacturer[] $rows */
		$rows = Manufacturer::find()
			->with(['manufacturerTexts' => function (ActiveQuery $query) {
				$query->where(['manufacturer_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->where([
				'status' => Manufacturer::STATUS_PUBLISHED,
				'deleted_at' => null
			])
			->all()
		;

		$out = [];
		foreach ($rows as $row) {
			$out[] = [
				'manufacturer_id' => $row->manufacturer_id,
				'title' => $row->manufacturerTexts[0]->title,
				'url_key' => $row->manufacturerTexts[0]->url_key,
				'products_qty' => isset($manufactureQtyRows[$row->manufacturer_id]) ? $manufactureQtyRows[$row->manufacturer_id] : 0
			];
		}

		return $out;
	}

	protected function countPriceRange(): array|null
	{
		$values = $this->getValuesWithout($this->values, ['price_min', 'price_max']);

		$productSearch = new ProductSearch();
		$productSearch->load($values, '');
		if (!$productSearch->validate()) {
			return null;
		}

		$row = $productSearch->makeQuery()->getQuery()
			->limit(-1)
			->offset(-1)
			->orderBy([])
			->innerJoin(
				'final_price',
				'final_price.point_id = :point and final_price.item_id = vw.item_id', [
					'point' => PointSale::DEFAULT_POINT
				]
			)
			->innerJoin('price', 'price.price_id = final_price.price_id and price.alias = :sellingPrice', [
				'sellingPrice' => Price::ALIAS_SELLING_PRICE
			])
			->select(new Expression('least(min(final_price.value), min(final_price.min)) as "min", greatest(max(final_price.value), max(final_price.max)) as "max"'))
			->asArray()
			->one()
		;

		return $row;
	}

	protected function getValuesWithout(array $inputArray, array $omitKeys): array
	{
		return array_filter($inputArray, function ($value, $key) use ($omitKeys) {
			return !in_array($key, $omitKeys);
		}, ARRAY_FILTER_USE_BOTH);
	}
}
