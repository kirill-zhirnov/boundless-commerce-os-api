<?php

namespace app\modules\inventory\models;

use Yii;

/**
 * This is the model class for table "inventory_price".
 *
 * @property int $item_id
 * @property int $price_id
 * @property float $value
 * @property int $currency_id
 * @property float|null $old
 *
 * @property Currency $currency
 * @property InventoryItem $item
 * @property Price $price
 */
class InventoryPrice extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'inventory_price';
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
			[['item_id', 'price_id', 'value', 'currency_id'], 'required'],
			[['item_id', 'price_id', 'currency_id'], 'default', 'value' => null],
			[['item_id', 'price_id', 'currency_id'], 'integer'],
			[['value', 'old'], 'number'],
			[['item_id', 'price_id'], 'unique', 'targetAttribute' => ['item_id', 'price_id']]
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'item_id' => 'Item ID',
			'price_id' => 'Price ID',
			'value' => 'Value',
			'currency_id' => 'Currency ID',
			'old' => 'Old',
		];
	}

	/**
	 * Gets query for [[Currency]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCurrency()
	{
		return $this->hasOne(Currency::className(), ['currency_id' => 'currency_id']);
	}

	/**
	 * Gets query for [[Item]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getItem()
	{
		return $this->hasOne(InventoryItem::className(), ['item_id' => 'item_id']);
	}

	/**
	 * Gets query for [[Price]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPrice()
	{
		return $this->hasOne(Price::className(), ['price_id' => 'price_id']);
	}
}
