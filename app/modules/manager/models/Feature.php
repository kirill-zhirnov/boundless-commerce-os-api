<?php

namespace app\modules\manager\models;

use Yii;

/**
 * This is the model class for table "feature".
 *
 * @property int $feature_id
 * @property string $alias
 *
 * @property TariffLimit[] $tariffLimits
 * @property Tariff[] $tariffs
 */
class Feature extends \yii\db\ActiveRecord
{
	const ALIAS_STORAGE_LIMIT = 'storageLimit';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'feature';
	}

	/**
	 * @return \yii\db\Connection the database connection used by this AR class.
	 */
	public static function getDb()
	{
		return Yii::$app->get('managerDb');
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['alias'], 'required'],
			[['alias'], 'string', 'max' => 20],
			[['alias'], 'unique'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'feature_id' => 'Feature ID',
			'alias' => 'Alias',
		];
	}

	/**
	 * Gets query for [[TariffLimits]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getTariffLimits()
	{
		return $this->hasMany(TariffLimit::class, ['feature_id' => 'feature_id']);
	}

	/**
	 * Gets query for [[Tariffs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getTariffs()
	{
		return $this->hasMany(Tariff::class, ['tariff_id' => 'tariff_id'])->viaTable('tariff_limit', ['feature_id' => 'feature_id']);
	}
}
