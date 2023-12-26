<?php

namespace app\modules\catalog\models;

use Yii;
use app\modules\system\models\Lang;
use yii\db\Query;

/**
 * This is the model class for table "characteristic".
 *
 * @property int $characteristic_id
 * @property int|null $parent_id
 * @property int|null $group_id
 * @property string|null $type
 * @property string|null $system_type
 * @property string|null $alias
 * @property int $sort
 * @property array $cases_in_variants
 *
 * @property CharacteristicProductVal[] $characteristicProductVals
 * @property CharacteristicProp $characteristicProp
 * @property CharacteristicText[] $characteristicTexts
 * @property CharacteristicText $characteristicTextDefault
 * @property CharacteristicTypeCase[] $characteristicTypeCases
 * @property CharacteristicVariantVal[] $characteristicVariantVals
 * @property Characteristic[] $characteristics
 * @property FilterField[] $filterFields
 * @property Filter[] $filters
 * @property CommodityGroup $group
 * @property Lang[] $langs
 * @property Characteristic $parent
 */
class Characteristic extends \yii\db\ActiveRecord
{
	const TYPE_CHECKBOX = 'checkbox';
	const TYPE_RADIO = 'radio';
	const TYPE_SELECT = 'select';
	const TYPE_TEXT = 'text';
	const TYPE_TEXTAREA = 'textarea';
	const TYPE_WYSIWYG = 'wysiwyg';

	public $cases_in_variants;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'characteristic';
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
			[['parent_id', 'group_id', 'sort'], 'default', 'value' => null],
			[['parent_id', 'group_id', 'sort'], 'integer'],
			[['type', 'system_type', 'alias'], 'string'],
			[['sort'], 'required'],
			[['group_id', 'alias'], 'unique', 'targetAttribute' => ['group_id', 'alias']],
		];
	}

	public function afterFind()
	{
		parent::afterFind();

		if (isset($this->cases_in_variants)) {
			$this->cases_in_variants = json_decode($this->cases_in_variants, true);
		}

		return $this;
	}


	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'characteristic_id' => 'Characteristic ID',
			'parent_id' => 'Parent ID',
			'group_id' => 'Group ID',
			'type' => 'Type',
			'system_type' => 'System Type',
			'alias' => 'Alias',
			'sort' => 'Sort',
		];
	}

	public static function isMultiCaseType(string $type): bool
	{
		return in_array($type, [self::TYPE_CHECKBOX, self::TYPE_RADIO, self::TYPE_SELECT]);
	}

	public function isMultiCase(): bool
	{
		return self::isMultiCaseType($this->type);
	}

	/**
	 * Gets query for [[CharacteristicProductVals]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristicProductVals()
	{
		return $this->hasMany(CharacteristicProductVal::className(), ['characteristic_id' => 'characteristic_id']);
	}

	/**
	 * Gets query for [[CharacteristicProp]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristicProp()
	{
		return $this->hasOne(CharacteristicProp::class, ['characteristic_id' => 'characteristic_id']);
	}

	/**
	 * Gets query for [[CharacteristicTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristicTexts()
	{
		return $this->hasMany(CharacteristicText::class, ['characteristic_id' => 'characteristic_id']);
	}

	public function getCharacteristicTextDefault()
	{
		return $this->hasOne(CharacteristicText::class, ['characteristic_id' => 'characteristic_id'])
			->andWhere(['characteristic_text.lang_id' => Lang::DEFAULT_LANG])
		;
	}

	/**
	 * Gets query for [[CharacteristicTypeCases]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristicTypeCases()
	{
		return $this->hasMany(CharacteristicTypeCase::class, ['characteristic_id' => 'characteristic_id']);
	}

	/**
	 * Gets query for [[CharacteristicVariantVals]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristicVariantVals()
	{
		return $this->hasMany(CharacteristicVariantVal::className(), ['characteristic_id' => 'characteristic_id']);
	}

	/**
	 * Gets query for [[Characteristics]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristics()
	{
		return $this->hasMany(Characteristic::class, ['parent_id' => 'characteristic_id']);
	}

	/**
	 * Gets query for [[FilterFields]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getFilterFields()
	{
		return $this->hasMany(FilterField::class, ['characteristic_id' => 'characteristic_id']);
	}

	/**
	 * Gets query for [[Filters]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getFilters()
	{
		return $this->hasMany(Filter::class, ['filter_id' => 'filter_id'])->viaTable('filter_field', ['characteristic_id' => 'characteristic_id']);
	}

	/**
	 * Gets query for [[Group]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getGroup()
	{
		return $this->hasOne(CommodityGroup::class, ['group_id' => 'group_id']);
	}

	/**
	 * Gets query for [[Langs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLangs()
	{
		return $this->hasMany(Lang::class, ['lang_id' => 'lang_id'])->viaTable('characteristic_text', ['characteristic_id' => 'characteristic_id']);
	}

	/**
	 * Gets query for [[Parent]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getParent()
	{
		return $this->hasOne(Characteristic::class, ['characteristic_id' => 'parent_id']);
	}

	public static function loadProductCharacteristic($compiled, array $excludeCharacteristics = []): array
	{
		$characteristicAliases = is_array($compiled) ? array_keys($compiled) : [];
		if (empty($characteristicAliases)) {
			return [];
		}

		/** @var \yii\db\Connection $db */
		$db = Yii::$app->get('instanceDb');

		$query = new Query();
		$query
			->select(['vw.characteristic_id', 'vw.parent_id', 'vw.title', 'vw.alias', 'vw.type', 'vw.is_folder'])
			->from('vw_characteristic_grid as vw')
			->where(['vw.lang_id' => Lang::DEFAULT_LANG])
            ->andWhere([
                'or',
                ['vw.alias' => $characteristicAliases],
                ['is_folder' => true]
            ])
//			->andWhere('vw.characteristic_id in (' . implode(',', $characteristicIds) . ') or (is_folder is true)')
			->orderBy(['vw.tree_sort' => SORT_ASC])
		;

		if ($excludeCharacteristics) {
			$query->andWhere('vw.characteristic_id not in (' . implode(',', $excludeCharacteristics) . ')');
		}

		$caseIds = array_map('intval', array_reduce($compiled, function ($carry, $item) {
			if (is_array($item)) {
				$carry = array_merge($carry, $item);
			}
			return $carry;
		}, []));

		if ($caseIds) {
			$query
				->addSelect(['c_case.case_id', 'case_text.title as case_title'])
				->leftJoin('characteristic_type_case as c_case', 'c_case.characteristic_id = vw.characteristic_id and c_case.case_id in (' . implode(',', $caseIds) . ')')
				->leftJoin('characteristic_type_case_text as case_text', 'c_case.case_id = case_text.case_id and case_text.lang_id = ' . Lang::DEFAULT_LANG)
				->addOrderBy(['c_case.sort' => SORT_ASC])
			;
		}

		$rows = $query->all($db);

		$out = [];
		$key2Id = [];
		foreach ($rows as $row) {
			if ($row['is_folder']) {
				$out[] = [
					'id' => $row['characteristic_id'],
					'title' => $row['title'],
					'is_folder' => $row['is_folder'],
					'children' => []
				];
				$key2Id[$row['characteristic_id']] = sizeof($out) - 1;
			} else {
				if ($row['parent_id'] && isset($key2Id[$row['parent_id']])) {
					$addTo = &$out[$key2Id[$row['parent_id']]]['children'];
				} else {
					$addTo = &$out;
				}

				if (!isset($key2Id[$row['characteristic_id']])) {
					$characteristic = [
						'id' => $row['characteristic_id'],
						'title' => $row['title'],
						'alias' => $row['alias'],
						'type' => $row['type'],
					];

					if (self::isMultiCaseType($row['type'])) {
						$characteristic['cases'] = [];
					} else {
						$characteristic['value'] = (isset($compiled[$row['alias']]) && !is_array($compiled[$row['alias']])) ? $compiled[$row['alias']] : null;
					}

					$addTo[] = $characteristic;
					$key2Id[$row['characteristic_id']] = sizeof($addTo) - 1;
				}

				if (isset($row['case_id'])) {
					$caseCharacteristic = &$addTo[$key2Id[$row['characteristic_id']]];
//					var_dump($row['case_id'], $characteristic);
					if ($caseCharacteristic && isset($caseCharacteristic['cases'])) {
						$caseCharacteristic['cases'][] = [
							'id' => $row['case_id'],
							'title' => $row['case_title']
						];
					}
				}
			}
		}

		$out = self::removeEmptyCharacteristics($out);

		return $out;
	}

	public static function removeEmptyCharacteristics(array $characteristics): array
	{
		foreach ($characteristics as $key => $row) {
			if (isset($row['is_folder']) && $row['is_folder']) {
				$characteristics[$key]['children'] = self::removeEmptyCharacteristics($row['children']);
			}
		}

		return array_values(array_filter($characteristics, function ($item) {
			if (isset($item['is_folder']) && $item['is_folder']) {
				return $item['children'];
			} else {
				if (array_key_exists('value', $item)) {
					return $item['value'] !== null && $item['value'] !== '';
				}

				if (array_key_exists('cases', $item)) {
					return $item['cases'];
				}

				return false;
			}
		}));
	}

	public function fields(): array
	{
		$out = ['id' => fn (self $model) => $model->characteristic_id];

		if ($this->isRelationPopulated('characteristicTexts') && $this->characteristicTexts) {
			$out['title'] = function (self $model) {
				return $model->characteristicTexts[0]?->title;
			};
		}

		$out = array_merge($out, parent::fields());
		unset($out['system_type'], $out['characteristic_id']);

		if ($this->isRelationPopulated('characteristicTexts') && $this->characteristicTexts) {
			$out['help'] = function (self $model) {
				return $model->characteristicTexts[0]?->help;
			};
		}

		if ($this->isRelationPopulated('characteristicTypeCases')) {
			$out['cases'] = function (self $model) {
				return $model->characteristicTypeCases;
			};
		}

		if ($this->isRelationPopulated('characteristicProp')) {
			$out['prop'] = function (self $model) {
				return $model->characteristicProp;
			};
		}

		return $out;
	}
}
