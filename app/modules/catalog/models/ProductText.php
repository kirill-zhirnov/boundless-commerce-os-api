<?php

namespace app\modules\catalog\models;

use Yii;
use app\modules\system\models\Lang;

/**
 * This is the model class for table "product_text".
 *
 * @property int $product_id
 * @property int $lang_id
 * @property string|null $title
 * @property string|null $custom_title
 * @property string|null $custom_header
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property string|null $url_key
 * @property string|null $description
 *
 * @property Lang $lang
 * @property Product $product
 */
class ProductText extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'product_text';
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
			[['product_id', 'lang_id'], 'required'],
			[['product_id', 'lang_id'], 'default', 'value' => null],
			[['product_id', 'lang_id'], 'integer'],
			[['title', 'custom_title', 'custom_header', 'meta_description', 'meta_keywords', 'url_key', 'description'], 'string'],
			[['lang_id', 'url_key'], 'unique', 'targetAttribute' => ['lang_id', 'url_key']],
			[['product_id', 'lang_id'], 'unique', 'targetAttribute' => ['product_id', 'lang_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'product_id' => 'Product ID',
			'lang_id' => 'Lang ID',
			'title' => 'Title',
			'custom_title' => 'Custom Title',
			'custom_header' => 'Custom Header',
			'meta_description' => 'Meta Description',
			'meta_keywords' => 'Meta Keywords',
			'url_key' => 'Url Key',
			'description' => 'Description',
		];
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

	/**
	 * Gets query for [[Product]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProduct()
	{
		return $this->hasOne(Product::class, ['product_id' => 'product_id']);
	}

	public function fields(): array
	{
//		'title', 'url_key',
		$out = ['description', 'custom_title', 'meta_description'];
//		unset($out['lang_id'], $out['meta_keywords'], $out['product_id'], $out['custom_header']);

		return $out;
	}

	public function getDescriptionAsText(): string
	{
		return trim(strip_tags($this->description));
	}

	public function getShortDescription($maxLength = 100): string
	{
		$description = $this->getDescriptionAsText();
		if (mb_strlen($description) > $maxLength) {
			$description = substr($description, 0, $maxLength) . '...';
		}

		return $description;
	}
}
