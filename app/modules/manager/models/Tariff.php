<?php

namespace app\modules\manager\models;

use Yii;

/**
 * This is the model class for table "tariff".
 *
 * @property int $tariff_id
 * @property string $alias
 * @property string $billing_period
 * @property float $amount
 * @property int $currency_id
 * @property bool $is_default
 * @property string $created_at
 * @property string|null $deleted_at
 * @property string|null $wix_alias
 *
 * @property Currency $currency
 * @property Feature[] $features
 * @property InstanceLog[] $instanceLogs
 * @property Instance[] $instances
 * @property Lang[] $langs
 * @property TariffLimit[] $tariffLimits
 * @property TariffProp[] $tariffProps
 * @property TariffProp[] $tariffProps0
 * @property TariffText[] $tariffTexts
 */
class Tariff extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'tariff';
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
			[['alias', 'billing_period', 'amount', 'currency_id'], 'required'],
			[['billing_period'], 'string'],
			[['amount'], 'number'],
			[['currency_id'], 'default', 'value' => null],
			[['currency_id'], 'integer'],
			[['is_default'], 'boolean'],
			[['created_at', 'deleted_at'], 'safe'],
			[['alias', 'wix_alias'], 'string', 'max' => 20],
			[['alias'], 'unique'],
			[['is_default'], 'unique'],
			[['wix_alias'], 'unique'],
			[['currency_id'], 'exist', 'skipOnError' => true, 'targetClass' => Currency::class, 'targetAttribute' => ['currency_id' => 'currency_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'tariff_id' => 'Tariff ID',
			'alias' => 'Alias',
			'billing_period' => 'Billing Period',
			'amount' => 'Amount',
			'currency_id' => 'Currency ID',
			'is_default' => 'Is Default',
			'created_at' => 'Created At',
			'deleted_at' => 'Deleted At',
			'wix_alias' => 'Wix Alias',
		];
	}

	/**
	 * Gets query for [[Currency]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCurrency()
	{
		return $this->hasOne(Currency::class, ['currency_id' => 'currency_id']);
	}

	/**
	 * Gets query for [[Features]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getFeatures()
	{
		return $this->hasMany(Feature::class, ['feature_id' => 'feature_id'])->viaTable('tariff_limit', ['tariff_id' => 'tariff_id']);
	}

	/**
	 * Gets query for [[InstanceLogs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInstanceLogs()
	{
		return $this->hasMany(InstanceLog::class, ['tariff_id' => 'tariff_id']);
	}

	/**
	 * Gets query for [[Instances]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInstances()
	{
		return $this->hasMany(Instance::class, ['tariff_id' => 'tariff_id']);
	}

	/**
	 * Gets query for [[Langs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLangs()
	{
		return $this->hasMany(Lang::class, ['lang_id' => 'lang_id'])->viaTable('tariff_text', ['tariff_id' => 'tariff_id']);
	}

	/**
	 * Gets query for [[TariffLimits]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getTariffLimits()
	{
		return $this->hasMany(TariffLimit::class, ['tariff_id' => 'tariff_id']);
	}

	/**
	 * Gets query for [[TariffProps]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getTariffProps()
	{
		return $this->hasMany(TariffProp::class, ['tariff_id' => 'tariff_id']);
	}

	/**
	 * Gets query for [[TariffProps0]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getTariffProps0()
	{
		return $this->hasMany(TariffProp::class, ['next_tariff_id' => 'tariff_id']);
	}

	/**
	 * Gets query for [[TariffTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getTariffTexts()
	{
		return $this->hasMany(TariffText::class, ['tariff_id' => 'tariff_id']);
	}

	public function getFeaturesLimits(): array
	{
		$rows = self::getDb()->createCommand("
			select
				feature_id,
				feature.alias,
				tariff_limit.value
			from
				tariff_limit
				inner join feature using (feature_id)
			where
				tariff_id = :tariff
		")
			->bindValues(['tariff' => $this->tariff_id])
			->queryAll()
		;

		$out = [];
		foreach ($rows as $row) {
			$out[$row['alias']] = $row['value'];
		}

		return $out;
	}
}
