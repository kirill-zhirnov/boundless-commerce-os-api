<?php

namespace app\modules\saas\models;

use app\helpers\Util;
use Yii;

/**
 * This is the model class for table "app_token".
 *
 * @property int $token_id
 * @property string|null $name
 * @property string|null $client_id
 * @property string|null $secret
 * @property string|null $permanent_token
 * @property bool $require_exp
 * @property string $created_at
 */
class AppToken extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'app_token';
	}

	/**
	 * @return \yii\db\Connection the database connection used by this AR class.
	 */
	public static function getDb()
	{
		return Yii::$app->get('managerDb');
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules(): array
	{
		return [
			[['require_exp'], 'boolean'],
			[['created_at'], 'safe'],
			[['name'], 'string', 'max' => 255],
			[['client_id'], 'string', 'max' => 20],
			[['secret'], 'string', 'max' => 50],
			[['permanent_token'], 'string', 'max' => 300],
			[['client_id'], 'unique'],
			[['name'], 'unique'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels(): array
	{
		return [
			'token_id' => 'Token ID',
			'name' => 'Name',
			'client_id' => 'Client ID',
			'secret' => 'Secret',
			'permanent_token' => 'Permanent Token',
			'require_exp' => 'Require Exp',
			'created_at' => 'Created At',
		];
	}

	public static function createUniqueClientId(): self
	{
		$row = self::getDb()->createCommand('
			insert into app_token
				(client_id)
			values
				(:clientId)
			on conflict do nothing
			returning *
		')
			->bindValues(['clientId' => Util::getRndStr(17, 'letnum', false)])
			->queryOne()
		;

		if (!$row) {
			return self::createUniqueClientId();
		}

		return self::findOne($row['token_id']);
	}

	public function isPermanentToken(): bool
	{
		return ($this->permanent_token !== null || !$this->require_exp);
	}
}
