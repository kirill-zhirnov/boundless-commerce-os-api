<?php

namespace app\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "price_text".
 *
 * @property int $price_id
 * @property int $lang_id
 * @property string|null $title
 *
 * @property Lang $lang
 * @property Price $price
 */
class PriceText extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'price_text';
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
			[['price_id', 'lang_id'], 'required'],
			[['price_id', 'lang_id'], 'default', 'value' => null],
			[['price_id', 'lang_id'], 'integer'],
			[['title'], 'string'],
			[['price_id', 'lang_id'], 'unique', 'targetAttribute' => ['price_id', 'lang_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'price_id' => 'Price ID',
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
	 * Gets query for [[Price]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPrice()
	{
		return $this->hasOne(Price::class, ['price_id' => 'price_id']);
	}
}
