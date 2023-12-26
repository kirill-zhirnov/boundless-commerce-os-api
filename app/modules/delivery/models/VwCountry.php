<?php

namespace app\modules\delivery\models;

use app\modules\delivery\activeQueries\VwCountryQuery;
use Yii;

/**
 * This is the model class for table "vw_country".
 *
 * @property int|null $country_id
 * @property string|null $code
 * @property int|null $lang_id
 * @property string|null $title
 */
class VwCountry extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'vw_country';
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
			[['country_id', 'lang_id'], 'default', 'value' => null],
			[['country_id', 'lang_id'], 'integer'],
			[['title'], 'string'],
			[['code'], 'string', 'max' => 2],
			[['country_id', 'lang_id'], 'unique', 'targetAttribute' => ['country_id', 'lang_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'country_id' => 'Country ID',
			'code' => 'Code',
			'lang_id' => 'Lang ID',
			'title' => 'Title',
		];
	}

	public static function find(): VwCountryQuery
	{
		return new VwCountryQuery(get_called_class());
	}

	public function fields(): array
	{
		return [
			'country_id',
			'code',
			'title'
		];
	}
}
