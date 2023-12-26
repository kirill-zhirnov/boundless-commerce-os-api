<?php

namespace app\modules\saas\models;

use Yii;
use app\modules\manager\models\Instance;

/**
 * This is the model class for table "wix_app".
 *
 * @property int $wix_app_id
 * @property string $wix_instance_id
 * @property string $status
 * @property int|null $instance_id
 * @property string|null $refresh_token
 * @property string|null $data
 * @property string $created_at
 *
 * @property Instance $instance
 */
class WixApp extends \yii\db\ActiveRecord
{
	const STATUS_CREATING = 'creating';
	const STATUS_READY = 'ready';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'wix_app';
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
	public function rules()
	{
		return [
			[['wix_instance_id'], 'required'],
			[['status'], 'string'],
			[['instance_id'], 'default', 'value' => null],
			[['instance_id'], 'integer'],
			[['data', 'created_at'], 'safe'],
			[['wix_instance_id'], 'string', 'max' => 50],
			[['refresh_token'], 'string', 'max' => 500],
//			[['instance_id'], 'unique'],
//			[['wix_instance_id'], 'unique'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'wix_app_id' => 'Wix App ID',
			'wix_instance_id' => 'Wix Instance ID',
			'status' => 'Status',
			'instance_id' => 'Instance ID',
			'refresh_token' => 'Refresh Token',
			'data' => 'Data',
			'created_at' => 'Created At',
		];
	}

	/**
	 * Gets query for [[Instance]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInstance()
	{
		return $this->hasOne(Instance::class, ['instance_id' => 'instance_id']);
	}

	public static function createUniqueWixApp(string $wixInstanceId): self|null
	{
		$row = self::getDb()->createCommand('
			insert into wix_app
				(wix_instance_id)
			values
				(:instanceId)
			on conflict do nothing
			returning *
		')
			->bindValues(['instanceId' => $wixInstanceId])
			->queryOne()
		;

		if ($row) {
			return self::findOne($row['wix_app_id']);
		}

		return null;
	}

	public function isReady(): bool
	{
		return $this->status === self::STATUS_READY;
	}
}
