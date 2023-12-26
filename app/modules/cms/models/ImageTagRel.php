<?php

namespace app\modules\cms\models;

use Yii;
use app\modules\catalog\models\ProductImage;

/**
 * This is the model class for table "image_tag_rel".
 *
 * @property int $image_tag_id
 * @property int $product_image_id
 *
 * @property ImageTag $imageTag
 * @property ProductImage $productImage
 */
class ImageTagRel extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'image_tag_rel';
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
			[['image_tag_id', 'product_image_id'], 'required'],
			[['image_tag_id', 'product_image_id'], 'default', 'value' => null],
			[['image_tag_id', 'product_image_id'], 'integer'],
			[['image_tag_id', 'product_image_id'], 'unique', 'targetAttribute' => ['image_tag_id', 'product_image_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'image_tag_id' => 'Image Tag ID',
			'product_image_id' => 'Product Image ID',
		];
	}

	/**
	 * Gets query for [[ImageTag]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getImageTag()
	{
		return $this->hasOne(ImageTag::class, ['image_tag_id' => 'image_tag_id']);
	}

	/**
	 * Gets query for [[ProductImage]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductImage()
	{
		return $this->hasOne(ProductImage::class, ['product_image_id' => 'product_image_id']);
	}
}
