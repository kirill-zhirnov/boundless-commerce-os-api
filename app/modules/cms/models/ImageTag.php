<?php

namespace app\modules\cms\models;

use Yii;
use app\modules\catalog\models\ProductImage;

/**
 * This is the model class for table "image_tag".
 *
 * @property int $image_tag_id
 * @property string|null $title
 *
 * @property ImageTagRel[] $imageTagRels
 * @property ProductImage[] $productImages
 */
class ImageTag extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'image_tag';
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
			[['title'], 'string', 'max' => 100],
			[['lower(title::text)'], 'unique'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'image_tag_id' => 'Image Tag ID',
			'title' => 'Title',
		];
	}

	/**
	 * Gets query for [[ImageTagRels]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getImageTagRels()
	{
		return $this->hasMany(ImageTagRel::class, ['image_tag_id' => 'image_tag_id']);
	}

	/**
	 * Gets query for [[ProductImages]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductImages()
	{
		return $this->hasMany(ProductImage::class, ['product_image_id' => 'product_image_id'])
			->viaTable('image_tag_rel', ['image_tag_id' => 'image_tag_id']);
	}
}
