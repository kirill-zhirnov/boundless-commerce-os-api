<?php

namespace app\modules\payment\models;

use Yii;

/**
 * This is the model class for table "payment_request".
 *
 * @property int $payment_request_id
 * @property int $payment_transaction_id
 * @property string|null $request
 * @property string $created_at
 *
 * @property PaymentTransaction $paymentTransaction
 */
class PaymentRequest extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'payment_request';
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
			[['payment_transaction_id'], 'required'],
			[['payment_transaction_id'], 'default', 'value' => null],
			[['payment_transaction_id'], 'integer'],
			[['request', 'created_at'], 'safe'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'payment_request_id' => 'Payment Request ID',
			'payment_transaction_id' => 'Payment Transaction ID',
			'request' => 'Request',
			'created_at' => 'Created At',
		];
	}

	/**
	 * Gets query for [[PaymentTransaction]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPaymentTransaction()
	{
		return $this->hasOne(PaymentTransaction::class, ['payment_transaction_id' => 'payment_transaction_id']);
	}
}
