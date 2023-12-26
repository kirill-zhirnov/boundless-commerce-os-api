<?php

namespace app\modules\payment\models;

use Yii;

/**
 * This is the model class for table "payment_callback".
 *
 * @property int $payment_callback_id
 * @property int|null $payment_transaction_id
 * @property string|null $response
 * @property string $created_at
 *
 * @property PaymentTransaction $paymentTransaction
 */
class PaymentCallback extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'payment_callback';
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
			[['payment_transaction_id'], 'default', 'value' => null],
			[['payment_transaction_id'], 'integer'],
			[['response', 'created_at'], 'safe'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'payment_callback_id' => 'Payment Callback ID',
			'payment_transaction_id' => 'Payment Transaction ID',
			'response' => 'Response',
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
