<?php

namespace app\modules\catalog\models;

use Yii;
use app\modules\system\models\Lang;

/**
 * This is the model class for table "variant_text".
 *
 * @property int $variant_id
 * @property int $lang_id
 * @property string|null $title
 *
 * @property Lang $lang
 * @property Variant $variant
 */
class VariantText extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'variant_text';
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
			[['variant_id', 'lang_id'], 'required'],
			[['variant_id', 'lang_id'], 'default', 'value' => null],
			[['variant_id', 'lang_id'], 'integer'],
			[['title'], 'string'],
			[['variant_id', 'lang_id'], 'unique', 'targetAttribute' => ['variant_id', 'lang_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'variant_id' => 'Variant ID',
			'lang_id' => 'Lang ID',
			'title' => 'Title',
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
	 * Gets query for [[Variant]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getVariant()
	{
		return $this->hasOne(Variant::class, ['variant_id' => 'variant_id']);
	}
}
