<?php

namespace app\modules\user\models;

use app\helpers\Models;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "person_profile".
 *
 * @property int $person_id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $patronymic
 * @property int|null $group_id
 * @property string|null $phone
 * @property bool $receive_marketing_info
 * @property string|null $comment
 * @property string|null $custom_attrs
 *
 * @property CustomerGroup $group
 * @property Person $person
 */
class PersonProfile extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'person_profile';
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
			[['person_id'], 'required'],
			[['person_id', 'group_id'], 'default', 'value' => null],
			[['person_id', 'group_id'], 'integer'],
			[['first_name', 'last_name', 'patronymic', 'comment'], 'string'],
			[['receive_marketing_info'], 'boolean'],
			[['custom_attrs'], 'safe'],
			[['phone'], 'string', 'max' => 100],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'person_id' => 'Person ID',
			'first_name' => 'First Name',
			'last_name' => 'Last Name',
			'patronymic' => 'Patronymic',
			'group_id' => 'Group ID',
			'phone' => 'Phone',
			'receive_marketing_info' => 'Receive Marketing Info',
			'comment' => 'Comment',
			'custom_attrs' => 'Custom Attrs',
		];
	}

	public function beforeSave($insert): bool
	{
		if (!parent::beforeSave($insert)) {
			return false;
		}

		Models::emptyStr2Null($this, [
			'first_name', 'last_name', 'patronymic', 'phone', 'comment'
		]);

		return true;
	}

	/**
	 * Gets query for [[Group]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getGroup()
	{
		return $this->hasOne(CustomerGroup::className(), ['group_id' => 'group_id']);
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

	public function extendCustomAttrs(array $attrs)
	{
		$oldAttrs = $this->custom_attrs ?? [];

		$this->custom_attrs = ArrayHelper::merge($oldAttrs, $attrs);
		$this->save(false);
	}

	public function getFullName(): string
	{
		$out = [];
		if ($this->first_name) {
			$out[] = $this->first_name;
		}

		if ($this->last_name) {
			$out[] = $this->last_name;
		}

		return implode(' ', $out);
	}
}
