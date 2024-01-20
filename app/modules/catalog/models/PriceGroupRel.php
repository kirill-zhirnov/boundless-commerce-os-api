<?php

namespace app\modules\catalog\models;

use Yii;
use app\modules\user\models\CustomerGroup;

/**
 * This is the model class for table "price_group_rel".
 *
 * @property int $price_id
 * @property int $group_id
 *
 * @property CustomerGroup $group
 * @property Price $price
 */
class PriceGroupRel extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'price_group_rel';
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
			[['price_id', 'group_id'], 'required'],
			[['price_id', 'group_id'], 'default', 'value' => null],
			[['price_id', 'group_id'], 'integer'],
			[['price_id', 'group_id'], 'unique', 'targetAttribute' => ['price_id', 'group_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'price_id' => 'Price ID',
			'group_id' => 'Group ID',
		];
	}

	/**
	 * Gets query for [[Group]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getGroup()
	{
		return $this->hasOne(CustomerGroup::class, ['group_id' => 'group_id']);
	}

	/**
	 * Gets query for [[Price]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPrice()
	{
		return $this->hasOne(Price::class, ['price_id' => 'price_id']);
	}
}
