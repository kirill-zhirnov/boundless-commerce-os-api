<?php

namespace app\modules\system\models;

use Yii;
use app\modules\user\models\Person;

/**
 * This is the model class for table "admin_comment".
 *
 * @property int $comment_id
 * @property int $essence_id
 * @property int|null $person_id
 * @property string $comment
 * @property string $created_at
 *
 * @property Essence $essence
 * @property Person $person
 */
class AdminComment extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'admin_comment';
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
			[['essence_id', 'comment'], 'required'],
			[['essence_id', 'person_id'], 'default', 'value' => null],
			[['essence_id', 'person_id'], 'integer'],
			[['comment'], 'string'],
			[['created_at'], 'safe'],
			[['essence_id'], 'exist', 'skipOnError' => true, 'targetClass' => Essence::class, 'targetAttribute' => ['essence_id' => 'essence_id']],
			[['person_id'], 'exist', 'skipOnError' => true, 'targetClass' => Person::class, 'targetAttribute' => ['person_id' => 'person_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'comment_id' => Yii::t('app', 'Comment ID'),
			'essence_id' => Yii::t('app', 'Essence ID'),
			'person_id' => Yii::t('app', 'Person ID'),
			'comment' => Yii::t('app', 'Comment'),
			'created_at' => Yii::t('app', 'Created At'),
		];
	}

	/**
	 * Gets query for [[Essence]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getEssence()
	{
		return $this->hasOne(Essence::class, ['essence_id' => 'essence_id']);
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
