<?php

namespace app\modules\catalog\models;

use app\modules\cms\models\Image;
use Yii;

/**
 * This is the model class for table "vw_category_option".
 *
 * @property int|null $category_id
 * @property int|null $parent_id
 * @property int|null $site_id
 * @property int|null $lang_id
 * @property string|null $title
 * @property string|null $url_key
 * @property string|null $deleted_at
 * @property string|null $status
 * @property int|null $created_by
 * @property int|null $level
 * @property string|null $tree_sort
 *
 * @property CategoryProp $categoryProp
 * @property Image $image
 */
class VwCategoryOption extends \yii\db\ActiveRecord
{
	public $products_qty;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'vw_category_option';
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
			[['category_id', 'parent_id', 'site_id', 'lang_id', 'created_by', 'level'], 'default', 'value' => null],
			[['category_id', 'parent_id', 'site_id', 'lang_id', 'created_by', 'level'], 'integer'],
			[['title', 'url_key', 'status', 'tree_sort'], 'string'],
			[['deleted_at', 'image_id'], 'safe'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'category_id' => 'Category ID',
			'parent_id' => 'Parent ID',
			'site_id' => 'Site ID',
			'lang_id' => 'Lang ID',
			'title' => 'Title',
			'url_key' => 'Url Key',
			'deleted_at' => 'Deleted At',
			'status' => 'Status',
			'created_by' => 'Created By',
			'level' => 'Level',
			'tree_sort' => 'Tree Sort',
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
	 * Gets query for [[Image]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getImage()
	{
		return $this->hasOne(Image::class, ['image_id' => 'image_id']);
	}
}
