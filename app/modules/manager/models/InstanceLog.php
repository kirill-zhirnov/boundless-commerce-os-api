<?php

namespace app\modules\manager\models;

use Yii;

/**
 * This is the model class for table "instance_log".
 *
 * @property int $log_id
 * @property int $instance_id
 * @property string $action
 * @property string|null $status
 * @property string|null $transaction_type
 * @property float|null $amount
 * @property int|null $tariff_id
 * @property string|null $data
 * @property string $ts
 *
 * @property Instance $instance
 * @property Tariff $tariff
 */
class InstanceLog extends \yii\db\ActiveRecord
{
	const ACTION_CHANGE_STATUS = 'changeStatus';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'instance_log';
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
			[['instance_id', 'action'], 'required'],
			[['instance_id', 'tariff_id'], 'default', 'value' => null],
			[['instance_id', 'tariff_id'], 'integer'],
			[['action', 'status', 'transaction_type'], 'string'],
			[['amount'], 'number'],
			[['data', 'ts'], 'safe'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'log_id' => 'Log ID',
			'instance_id' => 'Instance ID',
			'action' => 'Action',
			'status' => 'Status',
			'transaction_type' => 'Transaction Type',
			'amount' => 'Amount',
			'tariff_id' => 'Tariff ID',
			'data' => 'Data',
			'ts' => 'Ts',
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

	/**
	 * Gets query for [[Tariff]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getTariff()
	{
		return $this->hasOne(Tariff::class, ['tariff_id' => 'tariff_id']);
	}
}
