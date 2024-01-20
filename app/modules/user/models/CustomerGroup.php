<?php

namespace app\modules\user\models;

use Yii;
use yii\db\Expression;

/**
 * This is the model class for table "customer_group".
 *
 * @property int $group_id
 * @property string|null $alias
 * @property string $created_at
 * @property string|null $deleted_at
 * @property string|null $title
 *
 * @property Person[] $people
 * @property PersonGroupRel[] $personGroupRels
 */
class CustomerGroup extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'customer_group';
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
			[['alias', 'title'], 'string'],
			[['created_at', 'deleted_at'], 'safe'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'group_id' => 'Group ID',
			'alias' => 'Alias',
			'created_at' => 'Created At',
			'deleted_at' => 'Deleted At',
			'title' => 'Title',
		];
	}

	/**
	 * Gets query for [[People]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPeople()
	{
		return $this->hasMany(Person::class, ['person_id' => 'person_id'])->viaTable('person_group_rel', ['group_id' => 'group_id']);
	}

	/**
	 * Gets query for [[PersonGroupRels]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPersonGroupRels()
	{
		return $this->hasMany(PersonGroupRel::class, ['group_id' => 'group_id']);
	}

	public function safeDelete(): void
	{
		$this->deleted_at = new Expression('now()');
		$this->save(false);
	}

	public function fields(): array
	{
		return ['group_id', 'title', 'alias', 'deleted_at'];
	}
}
