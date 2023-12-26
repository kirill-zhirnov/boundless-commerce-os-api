<?php

namespace app\modules\catalog\models;

use Yii;
use app\modules\system\models\Lang;

/**
 * This is the model class for table "characteristic_text".
 *
 * @property int $characteristic_id
 * @property int $lang_id
 * @property string|null $title
 * @property string|null $help
 *
 * @property Characteristic $characteristic
 * @property Lang $lang
 */
class CharacteristicText extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'characteristic_text';
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
			[['characteristic_id', 'lang_id'], 'required'],
			[['characteristic_id', 'lang_id'], 'default', 'value' => null],
			[['characteristic_id', 'lang_id'], 'integer'],
			[['title', 'help'], 'string'],
			[['characteristic_id', 'lang_id'], 'unique', 'targetAttribute' => ['characteristic_id', 'lang_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'characteristic_id' => 'Characteristic ID',
			'lang_id' => 'Lang ID',
			'title' => 'Title',
			'help' => 'Help',
		];
	}

	/**
	 * Gets query for [[Characteristic]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristic()
	{
		return $this->hasOne(Characteristic::class, ['characteristic_id' => 'characteristic_id']);
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
