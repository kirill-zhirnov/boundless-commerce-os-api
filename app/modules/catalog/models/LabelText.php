<?php

namespace app\modules\catalog\models;

use Yii;
use app\modules\system\models\Lang;

/**
 * This is the model class for table "label_text".
 *
 * @property int $label_id
 * @property int $lang_id
 * @property string|null $title
 *
 * @property Label $label
 * @property Lang $lang
 */
class LabelText extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'label_text';
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
			[['label_id', 'lang_id'], 'required'],
			[['label_id', 'lang_id'], 'default', 'value' => null],
			[['label_id', 'lang_id'], 'integer'],
			[['title'], 'string', 'max' => 255],
			[['label_id', 'lang_id'], 'unique', 'targetAttribute' => ['label_id', 'lang_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'label_id' => 'Label ID',
			'lang_id' => 'Lang ID',
			'title' => 'Title',
		];
	}

	/**
	 * Gets query for [[Label]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLabel()
	{
		return $this->hasOne(Label::class, ['label_id' => 'label_id']);
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
