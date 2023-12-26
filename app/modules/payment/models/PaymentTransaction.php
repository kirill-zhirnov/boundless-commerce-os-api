<?php

namespace app\modules\payment\models;

use Yii;
use app\modules\system\models\Currency;
use app\modules\orders\models\Orders;
use app\modules\user\models\Person;

/**
 * This is the model class for table "payment_transaction".
 *
 * @property int $payment_transaction_id
 * @property int $payment_method_id
 * @property string $status
 * @property float $mark_up_amount
 * @property float $total_amount
 * @property int $currency_id
 * @property string|null $external_id
 * @property int|null $order_id
 * @property int|null $person_id
 * @property string|null $data
 * @property string|null $error
 * @property string $created_at
 *
 * @property Currency $currency
 * @property Orders $order
 * @property PaymentCallback[] $paymentCallbacks
 * @property PaymentMethod $paymentMethod
 * @property PaymentRequest[] $paymentRequests
 * @property Person $person
 */
class PaymentTransaction extends \yii\db\ActiveRecord
{
	const STATUS_CREATED = 'created';
	const STATUS_AWAITING_FOR_CALLBACK = 'awaitingForCallback';
	const STATUS_COMPLETED = 'completed';
	const STATUS_CANCELLED = 'cancelled';
	const STATUS_EXCEPTION = 'exception';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'payment_transaction';
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
			[['payment_method_id', 'currency_id'], 'required'],
			[['payment_method_id', 'currency_id', 'order_id', 'person_id'], 'default', 'value' => null],
			[['payment_method_id', 'currency_id', 'order_id', 'person_id'], 'integer'],
			[['status'], 'string'],
			[['mark_up_amount', 'total_amount'], 'number'],
			[['data', 'error', 'created_at'], 'safe'],
			[['external_id'], 'string', 'max' => 255],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'payment_transaction_id' => 'Payment Transaction ID',
			'payment_method_id' => 'Payment Method ID',
			'status' => 'Status',
			'mark_up_amount' => 'Mark Up Amount',
			'total_amount' => 'Total Amount',
			'currency_id' => 'Currency ID',
			'external_id' => 'External ID',
			'order_id' => 'Order ID',
			'person_id' => 'Person ID',
			'data' => 'Data',
			'error' => 'Error',
			'created_at' => 'Created At',
		];
	}

	/**
	 * Gets query for [[Currency]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCurrency()
	{
		return $this->hasOne(Currency::class, ['currency_id' => 'currency_id']);
	}

	/**
	 * Gets query for [[Order]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrder()
	{
		return $this->hasOne(Orders::class, ['order_id' => 'order_id']);
	}

	/**
	 * Gets query for [[PaymentCallbacks]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPaymentCallbacks()
	{
		return $this->hasMany(PaymentCallback::class, ['payment_transaction_id' => 'payment_transaction_id']);
	}

	/**
	 * Gets query for [[PaymentMethod]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPaymentMethod()
	{
		return $this->hasOne(PaymentMethod::class, ['payment_method_id' => 'payment_method_id']);
	}

	/**
	 * Gets query for [[PaymentRequests]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPaymentRequests()
	{
		return $this->hasMany(PaymentRequest::class, ['payment_transaction_id' => 'payment_transaction_id']);
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

	public function saveStatusTo(string $status)
	{
		$this->status = self::STATUS_AWAITING_FOR_CALLBACK;
		$this->save(false);
	}

	public function isCompleted(): bool
	{
		return $this->status === self::STATUS_COMPLETED;
	}

	public function isAwaitingForCallback(): bool
	{
		return $this->status === self::STATUS_AWAITING_FOR_CALLBACK;
	}
}
