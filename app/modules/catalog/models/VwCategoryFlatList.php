<?php

namespace app\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "vw_category_flat_list".
 *
 * @property int|null $category_id
 * @property int|null $site_id
 * @property int|null $lang_id
 * @property int|null $level
 * @property string|null $tree_sort
 * @property string|null $joined_title
 *
 * @property Category $category
 * @property CategoryProp $categoryProp
 */
class VwCategoryFlatList extends \yii\db\ActiveRecord
{
	public $products_qty;
	public $children_qty;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'vw_category_flat_list';
	}

	public static function primaryKey()
	{
		return ['category_id'];
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
			[['category_id', 'site_id', 'lang_id', 'level'], 'default', 'value' => null],
			[['category_id', 'site_id', 'lang_id', 'level'], 'integer'],
			[['tree_sort', 'joined_title'], 'string'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'category_id' => 'Category ID',
			'site_id' => 'Site ID',
			'lang_id' => 'Lang ID',
			'level' => 'Level',
			'tree_sort' => 'Tree Sort',
			'joined_title' => 'Joined Title',
		];
	}

	/**
	 * Gets query for [[CategoryProp]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategoryProp()
	{
		return $this->hasOne(CategoryProp::class, ['category_id' => 'category_id']);
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
	 * Gets query for [[CategoryTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategoryTexts()
	{
		return $this->hasMany(CategoryText::class, ['category_id' => 'category_id']);
	}

	public function fields(): array
	{
		$fields = parent::fields();
		unset($fields['site_id'], $fields['lang_id']);

		$fields['title'] = function (self $model) {
			if ($this->isRelationPopulated('categoryTexts') && $this->categoryTexts[0]) {
				return $this->categoryTexts[0]->title;
			}
		};
		$fields['url_key'] = function (self $model) {
			if ($this->isRelationPopulated('categoryTexts') && $model->categoryTexts[0]) {
				return $model->categoryTexts[0]->url_key;
			}
		};
		$fields['parent_id'] = function (self $model) {
			if ($this->isRelationPopulated('category') && $model->category) {
				return $model->category->parent_id;
			}
		};
		$fields['image'] = function (self $model) {
			if ($this->isRelationPopulated('category') && $model->category->isRelationPopulated('image')) {
				return $model->category->image;
			}
		};
		$fields['custom_link'] = function (self $model) {
			if ($this->isRelationPopulated('categoryProp') && $model->categoryProp) {
				return $model->categoryProp->custom_link;
			}
		};

		if (isset($this->products_qty)) {
			$fields['products_qty'] = function (self $model) {
				return $model->products_qty;
			};
		}

		if (isset($this->children_qty)) {
			$fields['children_qty'] = function (self $model) {
				return $model->children_qty;
			};
		}

		return $fields;
	}
}
