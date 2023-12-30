<?php

namespace app\modules\catalog\models;

use Yii;
use app\modules\inventory\models\InventoryItem;
use app\modules\system\models\Currency;

/**
 * This is the model class for table "final_price".
 *
 * @property int $point_id
 * @property int $item_id
 * @property int $price_id
 * @property int $currency_id
 * @property float|null $value
 * @property float|null $min
 * @property float|null $max
 * @property bool $is_auto_generated
 * @property float|null $old
 * @property float|null $old_min
 * @property float|null $old_max
 *
 * @property Currency $currency
 * @property InventoryItem $item
 * @property PointSale $point
 * @property Price $price
 */
class FinalPrice extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'final_price';
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
			[['point_id', 'item_id', 'price_id', 'currency_id'], 'required'],
			[['point_id', 'item_id', 'price_id', 'currency_id'], 'default', 'value' => null],
			[['point_id', 'item_id', 'price_id', 'currency_id'], 'integer'],
			[['value', 'min', 'max', 'old', 'old_min', 'old_max'], 'number'],
			[['is_auto_generated'], 'boolean'],
			[['point_id', 'item_id', 'price_id'], 'unique', 'targetAttribute' => ['point_id', 'item_id', 'price_id']]
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'point_id' => 'Point ID',
			'item_id' => 'Item ID',
			'price_id' => 'Price ID',
			'currency_id' => 'Currency ID',
			'value' => 'Value',
			'min' => 'Min',
			'max' => 'Max',
			'is_auto_generated' => 'Is Auto Generated',
			'old' => 'Old',
			'old_min' => 'Old Min',
			'old_max' => 'Old Max',
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
	 * Gets query for [[Item]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getItem()
	{
		return $this->hasOne(InventoryItem::class, ['item_id' => 'item_id']);
	}

	/**
	 * Gets query for [[Point]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPoint()
	{
		return $this->hasOne(PointSale::class, ['point_id' => 'point_id']);
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

	public function fields(): array
	{
		$fields = parent::fields();
		unset($fields['point_id'], $fields['item_id'], $fields['price_id'], $fields['currency_id'], $fields['is_auto_generated']);

		$fields['price_alias'] = fn () => $this->price->alias;
		$fields['currency_alias'] = fn () => $this->currency->alias;

		return $fields;
	}
}
