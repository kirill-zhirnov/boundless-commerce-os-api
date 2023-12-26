<?php

namespace app\modules\user\models;

use Yii;

/**
 * This is the model class for table "person_role_rel".
 *
 * @property int $person_id
 * @property int $role_id
 *
 * @property Person $person
 * @property Role $role
 */
class PersonRoleRel extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'person_role_rel';
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
			[['person_id', 'role_id'], 'required'],
			[['person_id', 'role_id'], 'default', 'value' => null],
			[['person_id', 'role_id'], 'integer'],
			[['person_id', 'role_id'], 'unique', 'targetAttribute' => ['person_id', 'role_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'person_id' => 'Person ID',
			'role_id' => 'Role ID',
		];
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

	/**
	 * Gets query for [[Role]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getRole()
	{
		return $this->hasOne(Role::class, ['role_id' => 'role_id']);
	}
}
