<?php

namespace app\modules\payment\models;

use Yii;
use app\modules\delivery\models\DeliverySite;

/**
 * This is the model class for table "payment_method_delivery".
 *
 * @property int $payment_method_id
 * @property int $delivery_site_id
 *
 * @property DeliverySite $deliverySite
 * @property PaymentMethod $paymentMethod
 */
class PaymentMethodDelivery extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'payment_method_delivery';
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
			[['payment_method_id', 'delivery_site_id'], 'required'],
			[['payment_method_id', 'delivery_site_id'], 'default', 'value' => null],
			[['payment_method_id', 'delivery_site_id'], 'integer'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'payment_method_id' => 'Payment Method ID',
			'delivery_site_id' => 'Delivery Site ID',
		];
	}

	/**
	 * Gets query for [[DeliverySite]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getDeliverySite()
	{
		return $this->hasOne(DeliverySite::class, ['delivery_site_id' => 'delivery_site_id']);
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
}
