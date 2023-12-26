<?php

namespace app\modules\manager\models;

use Yii;

/**
 * This is the model class for table "tariff_limit".
 *
 * @property int $tariff_id
 * @property int $feature_id
 * @property string $value
 *
 * @property Feature $feature
 * @property Tariff $tariff
 */
class TariffLimit extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tariff_limit';
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
            [['tariff_id', 'feature_id', 'value'], 'required'],
            [['tariff_id', 'feature_id'], 'default', 'value' => null],
            [['tariff_id', 'feature_id'], 'integer'],
            [['value'], 'safe'],
            [['tariff_id', 'feature_id'], 'unique', 'targetAttribute' => ['tariff_id', 'feature_id']],
            [['feature_id'], 'exist', 'skipOnError' => true, 'targetClass' => Feature::class, 'targetAttribute' => ['feature_id' => 'feature_id']],
            [['tariff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Tariff::class, 'targetAttribute' => ['tariff_id' => 'tariff_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'tariff_id' => 'Tariff ID',
            'feature_id' => 'Feature ID',
            'value' => 'Value',
        ];
    }

    /**
     * Gets query for [[Feature]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFeature()
    {
        return $this->hasOne(Feature::class, ['feature_id' => 'feature_id']);
    }

    /**
     * Gets query for [[Tariff]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTariff()
    {
        return $this->hasOne(Tariff::class, ['tariff_id' => 'tariff_id']);
    }
}
