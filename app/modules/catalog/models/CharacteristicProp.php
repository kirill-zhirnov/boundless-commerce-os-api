<?php

namespace app\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "characteristic_prop".
 *
 * @property int $characteristic_id
 * @property bool $is_folder
 * @property bool $is_hidden
 * @property string|null $default_value
 *
 * @property Characteristic $characteristic
 */
class CharacteristicProp extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'characteristic_prop';
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
			[['characteristic_id'], 'required'],
			[['characteristic_id'], 'default', 'value' => null],
			[['characteristic_id'], 'integer'],
			[['is_folder', 'is_hidden'], 'boolean'],
			[['default_value'], 'string'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'characteristic_id' => 'Characteristic ID',
			'is_folder' => 'Is Folder',
			'is_hidden' => 'Is Hidden',
			'default_value' => 'Default Value',
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

	public function fields(): array
	{
		$out = parent::fields();
		unset($out['is_hidden'], $out['default_value'], $out['characteristic_id']);

		return $out;
	}
}
