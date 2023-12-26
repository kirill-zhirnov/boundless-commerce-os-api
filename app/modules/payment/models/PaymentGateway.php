<?php

namespace app\modules\payment\models;

use mysql_xdevapi\CollectionModify;
use Yii;

/**
 * This is the model class for table "payment_gateway".
 *
 * @property int $payment_gateway_id
 * @property string|null $alias
 * @property string|null $settings
 * @property int $sort
 */
class PaymentGateway extends \yii\db\ActiveRecord
{
	const ALIAS_CASH_ON_DELIVERY = 'cashOnDelivery';
	const ALIAS_PAYPAL = 'paypal';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'payment_gateway';
	}

	/**
	 * @return \yii\db\Connection the database connection used by this AR class.
	 */
	public static function getDb()
	{
		return Yii::$app->get('instanceDb');
	}

	public static function primaryKey()
	{
		return ['payment_gateway_id'];
	}

	public function isCashOnDelivery(): bool
	{
		return $this->alias === self::ALIAS_CASH_ON_DELIVERY;
	}

	public function isPayPal(): bool
	{
		return $this->alias === self::ALIAS_PAYPAL;
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['payment_gateway_id', 'sort'], 'required'],
			[['payment_gateway_id', 'sort'], 'default', 'value' => null],
			[['payment_gateway_id', 'sort'], 'integer'],
			[['settings'], 'safe'],
			[['alias'], 'string', 'max' => 20],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'payment_gateway_id' => 'Payment Gateway ID',
			'alias' => 'Alias',
			'settings' => 'Settings',
			'sort' => 'Sort',
		];
	}
}
