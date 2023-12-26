<?php

namespace app\modules\catalog\models;

use Yii;
use app\modules\system\models\Lang;

/**
 * This is the model class for table "category_text".
 *
 * @property int $category_id
 * @property int $lang_id
 * @property string|null $title
 * @property string|null $custom_title
 * @property string|null $custom_header
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property string|null $url_key
 * @property string|null $description_top
 * @property string|null $description_bottom
 *
 * @property Category $category
 * @property Lang $lang
 * @property Typearea $oldTypearea
 */
class CategoryText extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'category_text';
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
			[['category_id', 'lang_id'], 'required'],
			[['category_id', 'lang_id'], 'default', 'value' => null],
			[['category_id', 'lang_id'], 'integer'],
			[['title', 'custom_title', 'custom_header', 'meta_description', 'meta_keywords', 'url_key', 'description_top', 'description_bottom'], 'string'],
			[['lang_id', 'url_key'], 'unique', 'targetAttribute' => ['lang_id', 'url_key']],
			[['category_id', 'lang_id'], 'unique', 'targetAttribute' => ['category_id', 'lang_id']]
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'category_id' => 'Category ID',
			'lang_id' => 'Lang ID',
			'title' => 'Title',
			'custom_title' => 'Custom Title',
			'custom_header' => 'Custom Header',
			'meta_description' => 'Meta Description',
			'meta_keywords' => 'Meta Keywords',
			'url_key' => 'Url Key',
			'description_top' => 'Description Top',
			'description_bottom' => 'Description Bottom',
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
	 * Gets query for [[Lang]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLang()
	{
		return $this->hasOne(Lang::class, ['lang_id' => 'lang_id']);
	}

	public function getDescriptionTopAsText(): string
	{
		return trim(strip_tags($this->description_top));
	}

	public function getDescriptionBottomAsText(): string
	{
		return trim(strip_tags($this->description_bottom));
	}

	public function fields(): array
	{
		$out = parent::fields();
		unset($out['lang_id'], $out['meta_keywords'], $out['category_id'], $out['custom_header'], $out['title'], $out['url_key']);

		return $out;
	}
}
