<?php

namespace app\modules\delivery\models;

use Yii;
use app\modules\system\models\Lang;

/**
 * This is the model class for table "delivery_text".
 *
 * @property int|null $delivery_id
 * @property int|null $lang_id
 * @property string|null $title
 * @property string|null $description
 *
 * @property Delivery $delivery
 * @property Lang $lang
 */
class DeliveryText extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'delivery_text';
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
			[['delivery_id', 'lang_id'], 'default', 'value' => null],
			[['delivery_id', 'lang_id'], 'integer'],
			[['title', 'description'], 'string'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'delivery_id' => 'Delivery ID',
			'lang_id' => 'Lang ID',
			'title' => 'Title',
			'description' => 'Description',
		];
	}

	/**
	 * Gets query for [[Delivery]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getDelivery()
	{
		return $this->hasOne(Delivery::class, ['delivery_id' => 'delivery_id']);
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
}
