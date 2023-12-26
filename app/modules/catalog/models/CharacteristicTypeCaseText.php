<?php

namespace app\modules\catalog\models;

use Yii;
use app\modules\system\models\Lang;

/**
 * This is the model class for table "characteristic_type_case_text".
 *
 * @property int $case_id
 * @property int $lang_id
 * @property string|null $title
 *
 * @property CharacteristicTypeCase $case
 * @property Lang $lang
 */
class CharacteristicTypeCaseText extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'characteristic_type_case_text';
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
			[['case_id', 'lang_id'], 'required'],
			[['case_id', 'lang_id'], 'default', 'value' => null],
			[['case_id', 'lang_id'], 'integer'],
			[['title'], 'string'],
			[['case_id', 'lang_id'], 'unique', 'targetAttribute' => ['case_id', 'lang_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'case_id' => 'Case ID',
			'lang_id' => 'Lang ID',
			'title' => 'Title',
		];
	}

	/**
	 * Gets query for [[Case]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCase()
	{
		return $this->hasOne(CharacteristicTypeCase::class, ['case_id' => 'case_id']);
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
