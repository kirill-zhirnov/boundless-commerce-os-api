<?php

namespace app\modules\catalog\models;

use Yii;
use app\modules\system\models\Lang;

/**
 * This is the model class for table "product_image_text".
 *
 * @property int $product_image_id
 * @property int $lang_id
 * @property string|null $description
 * @property string|null $alt
 *
 * @property Lang $lang
 * @property ProductImage $productImage
 */
class ProductImageText extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'product_image_text';
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
			[['product_image_id', 'lang_id'], 'required'],
			[['product_image_id', 'lang_id'], 'default', 'value' => null],
			[['product_image_id', 'lang_id'], 'integer'],
			[['description', 'alt'], 'string'],
			[['product_image_id', 'lang_id'], 'unique', 'targetAttribute' => ['product_image_id', 'lang_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'product_image_id' => 'Product Image ID',
			'lang_id' => 'Lang ID',
			'description' => 'Description',
			'alt' => 'Alt',
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
	 * Gets query for [[ProductImage]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductImage()
	{
		return $this->hasOne(ProductImage::class, ['product_image_id' => 'product_image_id']);
	}
}
