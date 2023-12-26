<?php

namespace app\modules\cms\models;

use Yii;
use app\modules\system\models\Site;
use app\modules\system\models\Lang;
use app\modules\catalog\models\Product;
use app\modules\catalog\models\ProductImage;
use app\modules\catalog\models\Manufacturer;

/**
 * This is the model class for table "image".
 *
 * @property int $image_id
 * @property int|null $site_id
 * @property int|null $lang_id
 * @property string|null $name
 * @property int|null $size
 * @property string|null $path
 * @property int|null $width
 * @property int|null $height
 * @property string|null $used_in
 * @property string $created_at
 * @property string|null $deleted_at
 * @property string|null $mime_type
 *
 * @property Article[] $articles
 * @property InstagramMedia[] $instagramMedia
 * @property Lang $lang
 * @property Manufacturer[] $manufacturers
 * @property ProductImage[] $productImages
 * @property ProductReviewImg[] $productReviewImgs
 * @property Product[] $products
 * @property Site $site
 */
class Image extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'image';
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
			[['site_id', 'lang_id', 'size', 'width', 'height'], 'default', 'value' => null],
			[['site_id', 'lang_id', 'size', 'width', 'height'], 'integer'],
			[['name', 'used_in'], 'string'],
			[['created_at', 'deleted_at'], 'safe'],
			[['path', 'mime_type'], 'string', 'max' => 255],
			[['path'], 'unique'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'image_id' => 'Image ID',
			'site_id' => 'Site ID',
			'lang_id' => 'Lang ID',
			'name' => 'Name',
			'size' => 'Size',
			'path' => 'Path',
			'width' => 'Width',
			'height' => 'Height',
			'used_in' => 'Used In',
			'created_at' => 'Created At',
			'deleted_at' => 'Deleted At',
			'mime_type' => 'Mime Type',
		];
	}

	/**
	 * Gets query for [[Articles]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getArticles()
	{
		return $this->hasMany(Article::className(), ['image_id' => 'image_id']);
	}

	/**
	 * Gets query for [[InstagramMedia]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInstagramMedia()
	{
		return $this->hasMany(InstagramMedia::className(), ['image_id' => 'image_id']);
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
	 * Gets query for [[Manufacturers]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getManufacturers()
	{
		return $this->hasMany(Manufacturer::class, ['image_id' => 'image_id']);
	}

	/**
	 * Gets query for [[ProductImages]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductImages()
	{
		return $this->hasMany(ProductImage::class, ['image_id' => 'image_id']);
	}

	/**
	 * Gets query for [[ProductReviewImgs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductReviewImgs()
	{
		return $this->hasMany(ProductReviewImg::className(), ['image_id' => 'image_id']);
	}

	/**
	 * Gets query for [[Products]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProducts()
	{
		return $this->hasMany(Product::class, ['product_id' => 'product_id'])
			->viaTable('product_image', ['image_id' => 'image_id']);
	}

	/**
	 * Gets query for [[Site]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getSite()
	{
		return $this->hasOne(Site::class, ['site_id' => 'site_id']);
	}

	public function fields(): array
	{
		$out = parent::fields();
		unset($out['site_id'], $out['lang_id'], $out['deleted_at']);

		return $out;
	}
}
