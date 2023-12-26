<?php

namespace app\modules\user\models;

use Yii;

/**
 * This is the model class for table "role".
 *
 * @property int $role_id
 * @property string $title
 * @property string|null $alias
 *
 * @property AuthRule[] $authRules
 * @property Person[] $people
 * @property PersonRoleRel[] $personRoleRels
 * @property AuthResource[] $resources
 * @property AuthTask[] $tasks
 */
class Role extends \yii\db\ActiveRecord
{
	const ALIAS_GUEST = 'guest';
	const ALIAS_CLIENT = 'client';

	const ALIAS_ADMIN = 'admin';
	const ALIAS_GUEST_BUYER = 'guest-buyer';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'role';
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
			[['title'], 'required'],
			[['title', 'alias'], 'string', 'max' => 50],
			[['alias'], 'unique'],
			[['title'], 'unique'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'role_id' => 'Role ID',
			'title' => 'Title',
			'alias' => 'Alias',
		];
	}

	/**
	 * Gets query for [[AuthRules]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getAuthRules()
	{
		return $this->hasMany(AuthRule::className(), ['role_id' => 'role_id']);
	}

	/**
	 * Gets query for [[People]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPeople()
	{
		return $this->hasMany(Person::class, ['person_id' => 'person_id'])->viaTable('person_role_rel', ['role_id' => 'role_id']);
	}

	/**
	 * Gets query for [[PersonRoleRels]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPersonRoleRels()
	{
		return $this->hasMany(PersonRoleRel::class, ['role_id' => 'role_id']);
	}

	/**
	 * Gets query for [[Resources]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getResources()
	{
		return $this->hasMany(AuthResource::className(), ['resource_id' => 'resource_id'])->viaTable('auth_rule', ['role_id' => 'role_id']);
	}

	/**
	 * Gets query for [[Tasks]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getTasks()
	{
		return $this->hasMany(AuthTask::className(), ['task_id' => 'task_id'])->viaTable('auth_rule', ['role_id' => 'role_id']);
	}
}
