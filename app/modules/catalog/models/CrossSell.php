<?php

namespace app\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "cross_sell".
 *
 * @property int $cross_sell_id
 * @property int $category_id
 * @property int $product_id
 * @property int $rel_product_id
 * @property int|null $sort
 *
 * @property CrossSellCategory $category
 * @property Product $product
 * @property Product $relProduct
 */
class CrossSell extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'cross_sell';
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
			[['category_id', 'product_id', 'rel_product_id'], 'required'],
			[['category_id', 'product_id', 'rel_product_id', 'sort'], 'default', 'value' => null],
			[['category_id', 'product_id', 'rel_product_id', 'sort'], 'integer']
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'cross_sell_id' => 'Cross Sell ID',
			'category_id' => 'Category ID',
			'product_id' => 'Product ID',
			'rel_product_id' => 'Rel Product ID',
			'sort' => 'Sort',
		];
	}

	/**
	 * Gets query for [[Category]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategory()
	{
		return $this->hasOne(CrossSellCategory::class, ['category_id' => 'category_id']);
	}

	/**
	 * Gets query for [[Product]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProduct()
	{
		return $this->hasOne(Product::class, ['product_id' => 'product_id']);
	}

	/**
	 * Gets query for [[RelProduct]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getRelProduct()
	{
		return $this->hasOne(Product::class, ['product_id' => 'rel_product_id']);
	}
}
