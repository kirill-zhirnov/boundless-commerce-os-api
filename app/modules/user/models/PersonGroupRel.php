<?php

namespace app\modules\user\models;

use Yii;

/**
 * This is the model class for table "person_group_rel".
 *
 * @property int $person_id
 * @property int $group_id
 *
 * @property CustomerGroup $group
 * @property Person $person
 */
class PersonGroupRel extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'person_group_rel';
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
			[['person_id', 'group_id'], 'required'],
			[['person_id', 'group_id'], 'default', 'value' => null],
			[['person_id', 'group_id'], 'integer'],
			[['person_id', 'group_id'], 'unique', 'targetAttribute' => ['person_id', 'group_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'person_id' => 'Person ID',
			'group_id' => 'Group ID',
		];
	}

	/**
	 * Gets query for [[Group]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getGroup()
	{
		return $this->hasOne(CustomerGroup::class, ['group_id' => 'group_id']);
	}

	/**
	 * Gets query for [[Person]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPerson()
	{
		return $this->hasOne(Person::class, ['person_id' => 'person_id']);
	}
}
