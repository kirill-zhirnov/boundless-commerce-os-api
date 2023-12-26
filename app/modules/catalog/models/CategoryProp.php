<?php

namespace app\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "category_prop".
 *
 * @property int|null $category_id
 * @property bool $use_filter
 * @property int|null $filter_id
 * @property string|null $custom_link
 * @property string|null $sub_category_policy
 * @property bool $show_in_parent_page_menu
 * @property array|null $arbitrary_data
 *
 * @property Category $category
 * @property Filter $filter
 */
class CategoryProp extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'category_prop';
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
			[['category_id', 'filter_id'], 'default', 'value' => null],
			[['category_id', 'filter_id'], 'integer'],
			[['use_filter', 'show_in_parent_page_menu'], 'boolean'],
			[['sub_category_policy'], 'string'],
			[['custom_link'], 'string', 'max' => 500],
			[['arbitrary_data'], 'safe'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'category_id' => 'Category ID',
			'use_filter' => 'Use Filter',
			'filter_id' => 'Filter ID',
			'custom_link' => 'Custom Link',
			'sub_category_policy' => 'Sub Category Policy',
			'show_in_parent_page_menu' => 'Show In Parent Page Menu',
		];
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

	/**
	 * Gets query for [[Filter]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getFilter()
	{
		return $this->hasOne(Filter::class, ['filter_id' => 'filter_id']);
	}

	public function fields()
	{
		$out = parent::fields();

		unset($out['sub_category_policy'], $out['show_in_parent_page_menu'], $out['category_id']);

		return $out;
	}
}
