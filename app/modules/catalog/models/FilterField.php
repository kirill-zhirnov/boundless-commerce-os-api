<?php

namespace app\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "filter_field".
 *
 * @property int $field_id
 * @property int $filter_id
 * @property string $type
 * @property int|null $characteristic_id
 * @property int $sort
 *
 * @property Characteristic $characteristic
 * @property Filter $filter
 */
class FilterField extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'filter_field';
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
			[['filter_id', 'type', 'sort'], 'required'],
			[['filter_id', 'characteristic_id', 'sort'], 'default', 'value' => null],
			[['filter_id', 'characteristic_id', 'sort'], 'integer'],
			[['type'], 'string'],
			[['filter_id', 'characteristic_id'], 'unique', 'targetAttribute' => ['filter_id', 'characteristic_id']],
			[['filter_id', 'type'], 'unique', 'targetAttribute' => ['filter_id', 'type']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'field_id' => 'Field ID',
			'filter_id' => 'Filter ID',
			'type' => 'Type',
			'characteristic_id' => 'Characteristic ID',
			'sort' => 'Sort',
		];
	}

	/**
	 * Gets query for [[Characteristic]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristic()
	{
		return $this->hasOne(Characteristic::class, ['characteristic_id' => 'characteristic_id']);
	}

	/**
	 * Gets query for [[Filter]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getFilter()
	{
		return $this->hasOne(Filter::class, ['filter_id' => 'filter_id']);
	}

	public function fields(): array
	{
		$out = parent::fields();

		if ($this->isRelationPopulated('characteristic') && $this->characteristic) {
			$out['characteristic'] = function (self $model) {
				return $model->characteristic;
			};
		}

		return $out;
	}
}
