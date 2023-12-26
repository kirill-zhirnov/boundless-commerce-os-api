<?php

namespace app\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "tax_class".
 *
 * @property int $tax_class_id
 * @property string $title
 * @property bool $is_default
 * @property string $created_at
 *
 * @property ProductProp[] $productProps
 * @property TaxRate[] $taxRates
 */
class TaxClass extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'tax_class';
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
			[['title'], 'required'],
			[['is_default'], 'boolean'],
			[['created_at'], 'safe'],
			[['title'], 'string', 'max' => 50],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'tax_class_id' => 'Tax Class ID',
			'title' => 'Title',
			'is_default' => 'Is Default',
			'created_at' => 'Created At',
		];
	}

	/**
	 * Gets query for [[ProductProps]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductProps()
	{
		return $this->hasMany(ProductProp::class, ['tax_class_id' => 'tax_class_id']);
	}

	/**
	 * Gets query for [[TaxRates]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getTaxRates()
	{
		return $this->hasMany(TaxRate::class, ['tax_class_id' => 'tax_class_id']);
	}
}
