<?php

namespace app\modules\cms\models;

use app\modules\catalog\models\Category;
use Yii;

/**
 * This is the model class for table "category_menu_rel".
 *
 * @property int $category_id
 * @property int $block_id
 *
 * @property MenuBlock $block
 * @property Category $category
 */
class CategoryMenuRel extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'category_menu_rel';
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
			[['category_id', 'block_id'], 'required'],
			[['category_id', 'block_id'], 'default', 'value' => null],
			[['category_id', 'block_id'], 'integer'],
			[['category_id', 'block_id'], 'unique', 'targetAttribute' => ['category_id', 'block_id']],
			[['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::class, 'targetAttribute' => ['category_id' => 'category_id']],
			[['block_id'], 'exist', 'skipOnError' => true, 'targetClass' => MenuBlock::class, 'targetAttribute' => ['block_id' => 'block_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'category_id' => 'Category ID',
			'block_id' => 'Block ID',
		];
	}

	/**
	 * Gets query for [[Block]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getBlock()
	{
		return $this->hasOne(MenuBlock::class, ['block_id' => 'block_id']);
	}

	/**
	 * Gets query for [[Category]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategory()
	{
		return $this->hasOne(Category::class, ['category_id' => 'category_id']);
	}
}
