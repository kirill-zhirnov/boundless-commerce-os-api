<?php

namespace app\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "cross_sell_category".
 *
 * @property int $category_id
 * @property string $alias
 * @property string $title
 *
 * @property CrossSell[] $crossSells
 */
class CrossSellCategory extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'cross_sell_category';
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
			[['alias', 'title'], 'required'],
			[['alias'], 'string', 'max' => 20],
			[['title'], 'string', 'max' => 255],
			[['alias'], 'unique'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'category_id' => 'Category ID',
			'alias' => 'Alias',
			'title' => 'Title',
		];
	}

	/**
	 * Gets query for [[CrossSells]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCrossSells()
	{
		return $this->hasMany(CrossSell::class, ['category_id' => 'category_id']);
	}
}
