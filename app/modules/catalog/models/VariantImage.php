<?php

namespace app\modules\catalog\models;

use Yii;
use app\modules\cms\models\Image;

/**
 * This is the model class for table "variant_image".
 *
 * @property int $variant_image_id
 * @property int $variant_id
 * @property int $image_id
 * @property bool $is_default
 * @property string $created_at
 *
 * @property Image $image
 * @property Variant $variant
 */
class VariantImage extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'variant_image';
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
			[['variant_id', 'image_id', 'is_default'], 'required'],
			[['variant_id', 'image_id'], 'default', 'value' => null],
			[['variant_id', 'image_id'], 'integer'],
			[['is_default'], 'boolean'],
			[['created_at'], 'safe'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'variant_image_id' => 'Variant Image ID',
			'variant_id' => 'Variant ID',
			'image_id' => 'Image ID',
			'is_default' => 'Is Default',
			'created_at' => 'Created At',
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
	 * Gets query for [[Variant]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getVariant()
	{
		return $this->hasOne(Variant::class, ['variant_id' => 'variant_id']);
	}

	public function fields(): array
	{
		return [
			'variant_image_id',
			'image_id',
			'is_default'
		];
	}
}
