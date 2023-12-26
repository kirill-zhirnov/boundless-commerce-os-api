<?php

namespace app\modules\user\models;

use Yii;
use yii\db\Expression;

/**
 * This is the model class for table "person_auth".
 *
 * @property int $person_id
 * @property string|null $pass
 * @property string|null $email_confirmed
 *
 * @property Person $person
 */
class PersonAuth extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'person_auth';
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
			[['person_id'], 'default', 'value' => null],
			[['person_id'], 'integer'],
			[['email_confirmed'], 'safe'],
			[['pass'], 'string', 'max' => 100],
			[['person_id'], 'unique'],
			[['person_id'], 'exist', 'skipOnError' => true, 'targetClass' => Person::className(), 'targetAttribute' => ['person_id' => 'person_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'person_id' => 'Person ID',
			'pass' => 'Pass',
			'email_confirmed' => 'Email Confirmed',
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

	public static function setPass($personId, $password)
	{
		self::updateAll(['pass' => new Expression("crypt(:pass, gen_salt('bf'))", ['pass' => $password])], [
			'person_id' => $personId
		]);
	}
}
