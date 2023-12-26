<?php

namespace app\modules\catalog\models;

use Yii;
use app\modules\system\models\Lang;

/**
 * This is the model class for table "commodity_group_text".
 *
 * @property int $group_id
 * @property int $lang_id
 * @property string|null $title
 *
 * @property CommodityGroup $group
 * @property Lang $lang
 */
class CommodityGroupText extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'commodity_group_text';
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
			[['group_id', 'lang_id'], 'required'],
			[['group_id', 'lang_id'], 'default', 'value' => null],
			[['group_id', 'lang_id'], 'integer'],
			[['title'], 'string'],
			[['group_id', 'lang_id'], 'unique', 'targetAttribute' => ['group_id', 'lang_id']]
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'group_id' => 'Group ID',
			'lang_id' => 'Lang ID',
			'title' => 'Title',
		];
	}

	/**
	 * Gets query for [[Group]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getGroup()
	{
		return $this->hasOne(CommodityGroup::class, ['group_id' => 'group_id']);
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
