<?php

namespace app\modules\catalog\models;

use app\modules\catalog\activeQueries\FilterQuery;
use Yii;

/**
 * This is the model class for table "filter".
 *
 * @property int $filter_id
 * @property string|null $title
 * @property bool $is_default
 * @property string $created_at
 *
 * @property CategoryProp[] $categoryProps
 * @property Characteristic[] $characteristics
 * @property FilterField[] $filterFields
 */
class Filter extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'filter';
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
			[['is_default'], 'boolean'],
			[['created_at'], 'safe'],
			[['title'], 'string', 'max' => 255],
			[['is_default'], 'unique'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'filter_id' => 'Filter ID',
			'title' => 'Title',
			'is_default' => 'Is Default',
			'created_at' => 'Created At',
		];
	}

	/**
	 * Gets query for [[CategoryProps]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategoryProps()
	{
		return $this->hasMany(CategoryProp::class, ['filter_id' => 'filter_id']);
	}

	/**
	 * Gets query for [[Characteristics]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristics()
	{
		return $this->hasMany(Characteristic::class, ['characteristic_id' => 'characteristic_id'])->viaTable('filter_field', ['filter_id' => 'filter_id']);
	}

	/**
	 * Gets query for [[FilterFields]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getFilterFields()
	{
		return $this->hasMany(FilterField::class, ['filter_id' => 'filter_id']);
	}

	public static function find(): FilterQuery
	{
		return new FilterQuery(get_called_class());
	}

	public function fields(): array
	{
		$out = parent::fields();

		if ($this->isRelationPopulated('filterFields')) {
			$out['fields'] = function (self $model) {
				return $model->filterFields;
			};
		}

		return $out;
	}
}
