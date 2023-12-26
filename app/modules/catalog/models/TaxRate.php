<?php

namespace app\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "tax_rate".
 *
 * @property int $tax_rate_id
 * @property int $tax_class_id
 * @property string $title
 * @property float $rate
 * @property int $priority
 * @property bool $is_compound
 * @property bool $include_shipping
 * @property int|null $country_id
 * @property string|null $state_code
 * @property string $created_at
 *
 * @property TaxClass $taxClass
 */
class TaxRate extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'tax_rate';
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
			[['tax_class_id', 'title'], 'required'],
			[['tax_class_id', 'priority', 'country_id'], 'default', 'value' => null],
			[['tax_class_id', 'priority', 'country_id'], 'integer'],
			[['rate'], 'number'],
			[['is_compound', 'include_shipping'], 'boolean'],
			[['created_at'], 'safe'],
			[['title'], 'string', 'max' => 50],
			[['state_code'], 'string', 'max' => 10],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'tax_rate_id' => 'Tax Rate ID',
			'tax_class_id' => 'Tax Class ID',
			'title' => 'Title',
			'rate' => 'Rate',
			'priority' => 'Priority',
			'is_compound' => 'Is Compound',
			'include_shipping' => 'Include Shipping',
			'country_id' => 'Country ID',
			'state_code' => 'State Code',
			'created_at' => 'Created At',
		];
	}

	/**
	 * Gets query for [[TaxClass]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getTaxClass()
	{
		return $this->hasOne(TaxClass::class, ['tax_class_id' => 'tax_class_id']);
	}
}
