<?php

namespace app\modules\catalog\models;

use Yii;
use app\modules\cms\models\Image;
use app\modules\cms\models\ImageTag;
use app\modules\cms\models\ImageTagRel;
use app\modules\system\models\Lang;

/**
 * This is the model class for table "product_image".
 *
 * @property int $product_image_id
 * @property int $product_id
 * @property int $image_id
 * @property bool $is_default
 * @property int $sort
 * @property string|null $source_url
 *
 * @property Image $image
 * @property ImageTagRel[] $imageTagRels
 * @property ImageTag[] $imageTags
 * @property Lang[] $langs
 * @property Product $product
 * @property ProductImageText[] $productImageTexts
 */
class ProductImage extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'product_image';
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
			[['product_id', 'image_id', 'is_default', 'sort'], 'required'],
			[['product_id', 'image_id', 'sort'], 'default', 'value' => null],
			[['product_id', 'image_id', 'sort'], 'integer'],
			[['is_default'], 'boolean'],
			[['source_url'], 'string'],
			[['product_id', 'image_id'], 'unique', 'targetAttribute' => ['product_id', 'image_id']],
			[['product_id', 'is_default'], 'unique', 'targetAttribute' => ['product_id', 'is_default']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'product_image_id' => 'Product Image ID',
			'product_id' => 'Product ID',
			'image_id' => 'Image ID',
			'is_default' => 'Is Default',
			'sort' => 'Sort',
			'source_url' => 'Source Url',
		];
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

	/**
	 * Gets query for [[ImageTagRels]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getImageTagRels()
	{
		return $this->hasMany(ImageTagRel::class, ['product_image_id' => 'product_image_id']);
	}

	/**
	 * Gets query for [[ImageTags]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getImageTags()
	{
		return $this->hasMany(ImageTag::class, ['image_tag_id' => 'image_tag_id'])->viaTable('image_tag_rel', ['product_image_id' => 'product_image_id']);
	}

	/**
	 * Gets query for [[Langs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLangs()
	{
		return $this->hasMany(Lang::class, ['lang_id' => 'lang_id'])->viaTable('product_image_text', ['product_image_id' => 'product_image_id']);
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
	 * Gets query for [[ProductImageTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductImageTexts()
	{
		return $this->hasMany(ProductImageText::class, ['product_image_id' => 'product_image_id']);
	}

	public function fields()
	{
		$out = [
			'product_image_id',
//			'image_id',
			'is_default',
			'sort',
		];

		if ($this->isRelationPopulated('productImageTexts')) {
			$out['description'] = function (self $model) {
				return $model->productImageTexts[0]->description;
			};

			$out['alt'] = function (self $model) {
				return $model->productImageTexts[0]->alt;
			};
		}

		if ($this->isRelationPopulated('image')) {
			$out['image'] = function () {
				return $this->image;
			};
		}

		$out['tags'] = function () {
			if ($this->isRelationPopulated('imageTags') && $this->imageTags) {
				return $this->imageTags;
			}
		};

		return $out;
	}
}
