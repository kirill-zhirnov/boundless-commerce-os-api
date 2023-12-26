<?php

namespace app\modules\user\models;

use Yii;

/**
 * This is the model class for table "person_token".
 *
 * @property int $token_id
 * @property int $person_id
 * @property string $type
 * @property string $token_1
 * @property string $token_2
 * @property string|null $ip
 * @property string|null $valid_till
 * @property string|null $created_at
 *
 * @property Person $person
 */
class PersonToken extends \yii\db\ActiveRecord
{
	const TYPE_URL = 'url';
	const TYPE_REMEMBER_ME = 'rememberMe';
	const TYPE_MAGICK_LINK = 'magick-link';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'person_token';
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
			[['person_id', 'type', 'token_1', 'token_2'], 'required'],
			[['person_id'], 'default', 'value' => null],
			[['person_id'], 'integer'],
			[['ip'], 'string'],
			[['valid_till', 'created_at'], 'safe'],
			[['type', 'token_2'], 'string', 'max' => 30],
			[['token_1'], 'string', 'max' => 50],
			[['person_id'], 'exist', 'skipOnError' => true, 'targetClass' => Person::class, 'targetAttribute' => ['person_id' => 'person_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'token_id' => Yii::t('app', 'Token ID'),
			'person_id' => Yii::t('app', 'Person ID'),
			'type' => Yii::t('app', 'Type'),
			'token_1' => Yii::t('app', 'Token 1'),
			'token_2' => Yii::t('app', 'Token 2'),
			'ip' => Yii::t('app', 'Ip'),
			'valid_till' => Yii::t('app', 'Valid Till'),
			'created_at' => Yii::t('app', 'Created At'),
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

	public static function signParams(string $salt, array $params): string
	{
		$str = [$salt];
		foreach ($params as $key => $val) {
			$str[] = strval($key) . '=' . strval($val);
		}

		return md5(implode('|', $str));
	}
}
