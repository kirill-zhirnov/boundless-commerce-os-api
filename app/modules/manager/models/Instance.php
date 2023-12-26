<?php

namespace app\modules\manager\models;

use app\components\S3Buckets;
use app\components\s3Buckets\InstanceBucketTools;
use Yii;
use yii\db\Expression;

/**
 * This is the model class for table "instance".
 *
 * @property int $instance_id
 * @property string $status
 * @property string|null $path
 * @property int|null $client_id
 * @property int|null $tariff_id
 * @property float $balance
 * @property int|null $currency_id
 * @property bool $is_demo
 * @property string|null $available_since
 * @property string|null $unavailable_since
 * @property string|null $paid_till
 * @property string|null $remove_me
 * @property string|null $data
 * @property string|null $client_email
 * @property bool $is_free
 * @property int|null $from_sample_id
 * @property array|null $config
 *
 * @property Currency $currency
 * @property Sample $fromSample
 * @property Host[] $hosts
 * @property InstanceLog[] $instanceLogs
 * @property InstanceReview $instanceReview
 * @property Invoice[] $invoices
 * @property MailLog[] $mailLogs
 * @property PaymentTransaction[] $paymentTransactions
 * @property Sample[] $samples
 * @property Tariff $tariff
 * @property Task[] $tasks
 */
class Instance extends \yii\db\ActiveRecord
{
	const STATUS_AVAILABLE = 'available';
	const STATUS_AWAITING_FOR_REMOVE = 'awaitingForRemove';
	const STATUS_REMOVED = 'removed';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'instance';
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
			[['status'], 'required'],
			[['status'], 'string'],
			[['client_id', 'tariff_id', 'currency_id', 'from_sample_id'], 'default', 'value' => null],
			[['client_id', 'tariff_id', 'currency_id', 'from_sample_id'], 'integer'],
			[['balance'], 'number'],
			[['is_demo', 'is_free'], 'boolean'],
			[['available_since', 'unavailable_since', 'paid_till', 'remove_me', 'data', 'config'], 'safe'],
			[['path'], 'string', 'max' => 30],
			[['client_email'], 'string', 'max' => 255],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'instance_id' => 'Instance ID',
			'status' => 'Status',
			'path' => 'Path',
			'client_id' => 'Client ID',
			'tariff_id' => 'Tariff ID',
			'balance' => 'Balance',
			'currency_id' => 'Currency ID',
			'is_demo' => 'Is Demo',
			'available_since' => 'Available Since',
			'unavailable_since' => 'Unavailable Since',
			'paid_till' => 'Paid Till',
			'remove_me' => 'Remove Me',
			'data' => 'Data',
			'client_email' => 'Client Email',
			'is_free' => 'Is Free',
			'from_sample_id' => 'From Sample ID',
		];
	}

	public function isAvailable(): bool
	{
		return $this->status === self::STATUS_AVAILABLE;
	}

	/**
	 * Gets query for [[Currency]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	//  public function getCurrency()
	//  {
	//      return $this->hasOne(Currency::class, ['currency_id' => 'currency_id']);
	//  }

	/**
	 * Gets query for [[FromSample]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	//  public function getFromSample()
	//  {
	//      return $this->hasOne(Sample::className(), ['sample_id' => 'from_sample_id']);
	//  }

	/**
	 * Gets query for [[Hosts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	//  public function getHosts()
	//  {
	//      return $this->hasMany(Host::className(), ['instance_id' => 'instance_id']);
	//  }

	/**
	 * Gets query for [[InstanceLogs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	//  public function getInstanceLogs()
	//  {
	//      return $this->hasMany(InstanceLog::className(), ['instance_id' => 'instance_id']);
	//  }

	/**
	 * Gets query for [[InstanceReview]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	//  public function getInstanceReview()
	//  {
	//      return $this->hasOne(InstanceReview::className(), ['instance_id' => 'instance_id']);
	//  }

	/**
	 * Gets query for [[Invoices]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	//  public function getInvoices()
	//  {
	//      return $this->hasMany(Invoice::className(), ['instance_id' => 'instance_id']);
	//  }

	/**
	 * Gets query for [[MailLogs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	//  public function getMailLogs()
	//  {
	//      return $this->hasMany(MailLog::className(), ['instance_id' => 'instance_id']);
	//  }

	/**
	 * Gets query for [[PaymentTransactions]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	//  public function getPaymentTransactions()
	//  {
	//      return $this->hasMany(PaymentTransaction::className(), ['instance_id' => 'instance_id']);
	//  }

	/**
	 * Gets query for [[Samples]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	//  public function getSamples()
	//  {
	//      return $this->hasMany(Sample::className(), ['from_instance_id' => 'instance_id']);
	//  }

	/**
	 * Gets query for [[Tariff]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getTariff()
	{
		return $this->hasOne(Tariff::class, ['tariff_id' => 'tariff_id']);
	}

	/**
	 * Gets query for [[Tasks]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	//  public function getTasks()
	//  {
	//      return $this->hasMany(Task::className(), ['instance_id' => 'instance_id']);
	//  }

	public function getAuthSalt(): string
	{
		return $this->config['auth']['salt'];
	}

	public function makeInstanceBucketTools(): InstanceBucketTools
	{
		/** @var S3Buckets $s3Buckets */
		$s3Buckets = Yii::$app->s3Buckets;
		return $s3Buckets->makeInstanceBucketTools($this);
	}

	public function changeStatusToAwaitingForRemove(string|null $logReason = null)
	{
		$this->attributes = [
			'status' => self::STATUS_AWAITING_FOR_REMOVE,
			'remove_me' => new Expression('now()'),
			'available_since' => null,
			'unavailable_since' => null,
			'paid_till' => null
		];
		$this->save(false);

		$log = new InstanceLog();
		$log->attributes = [
			'instance_id' => $this->instance_id,
			'action' => InstanceLog::ACTION_CHANGE_STATUS,
			'status' => self::STATUS_AWAITING_FOR_REMOVE,
			'data' => [
				'reason' => $logReason
			]
		];
		$log->save(false);

		$this->refresh();
	}
}
