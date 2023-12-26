<?php

namespace app\modules\orders\models;

use Yii;
use app\modules\system\models\Lang;

/**
 * This is the model class for table "order_status_text".
 *
 * @property int $status_id
 * @property int $lang_id
 * @property string|null $title
 *
 * @property Lang $lang
 * @property OrderStatus $status
 */
class OrderStatusText extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'order_status_text';
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
			[['status_id', 'lang_id'], 'required'],
			[['status_id', 'lang_id'], 'default', 'value' => null],
			[['status_id', 'lang_id'], 'integer'],
			[['title'], 'string'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'status_id' => 'Status ID',
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
	 * Gets query for [[Status]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getStatus()
	{
		return $this->hasOne(OrderStatus::class, ['status_id' => 'status_id']);
	}
}
