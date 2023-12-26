<?php

namespace app\modules\user\models;

use app\helpers\Util;
use app\modules\user\components\TokenCreator;
use Yii;

/**
 * This is the model class for table "api_token".
 *
 * @property int $token_id
 * @property string $name
 * @property string $client_id
 * @property string $secret
 * @property string|null $permanent_token
 * @property bool $require_exp
 * @property bool $is_system
 * @property bool $can_manage
 * @property string $created_at
 * @property string|null $deleted_at
 */
class ApiToken extends \yii\db\ActiveRecord
{
	const WIX_SHOP_NAME = '__wix-shop';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'api_token';
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
			[['name', 'client_id', 'secret'], 'required'],
			[['require_exp', 'is_system'], 'boolean'],
			[['created_at', 'deleted_at'], 'safe'],
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
	public function attributeLabels()
	{
		return [
			'token_id' => 'Token ID',
			'name' => 'Name',
			'client_id' => 'Client ID',
			'secret' => 'Secret',
			'permanent_token' => 'Permanent Token',
			'require_exp' => 'Require Exp',
			'created_at' => 'Created At',
			'deleted_at' => 'Deleted At',
		];
	}

	public static function createUniqueClientId(): ApiToken
	{
		$row = self::getDb()->createCommand('
			insert into api_token
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

	public function hasManagementRights(): bool
	{
		return $this->can_manage;
	}

	public function isPermanentToken(): bool
	{
		return ($this->permanent_token !== null || !$this->require_exp);
	}

	public static function findOrCreateSystemToken(string $name, int $instanceId): self
	{
		$row = self::find()->where(['name' => $name])->one();
		if ($row) {
			return $row;
		}

		$row = self::createUniqueClientId();
		$row->attributes = [
			'name' => $name,
			'secret' => Util::getRndStr(33, 'letnum', false),
			'require_exp' => false,
			'is_system' => true
		];

		$tokenCreator = new TokenCreator($instanceId, $row->client_id, $row->secret);
		$row->permanent_token = $tokenCreator->create();

		if (!$row->save()) {
			throw new \RuntimeException('Cannot save: ' . print_r($row->getErrors(), 1));
		}

		return $row;
	}
}
