<?php

namespace app\modules\payment\models;

use app\modules\payment\activeQueries\PaymentMethodQuery;
use Yii;
use app\modules\system\models\Site;
use app\modules\system\models\Lang;
use app\modules\orders\models\Orders;

/**
 * This is the model class for table "payment_method".
 *
 * @property int $payment_method_id
 * @property int $site_id
 * @property int|null $payment_gateway_id
 * @property bool|null $for_all_delivery
 * @property string|null $config
 * @property float $mark_up
 * @property int $sort
 * @property string $created_at
 * @property string|null $deleted_at
 *
 * @property Orders[] $orders
 * @property PaymentMethodDelivery[] $paymentMethodDeliveries
 * @property PaymentMethodText $paymentMethodText
 * @property PaymentTransaction[] $paymentTransactions
 * @property Site $site
 * @property PaymentGateway $paymentGateway
 */
class PaymentMethod extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'payment_method';
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
			[['site_id', 'sort'], 'required'],
			[['site_id', 'payment_gateway_id', 'sort'], 'default', 'value' => null],
			[['site_id', 'payment_gateway_id', 'sort'], 'integer'],
			[['for_all_delivery'], 'boolean'],
			[['config', 'created_at', 'deleted_at'], 'safe'],
			[['mark_up'], 'number'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'payment_method_id' => 'Payment Method ID',
			'site_id' => 'Site ID',
			'payment_gateway_id' => 'Payment Gateway ID',
			'for_all_delivery' => 'For All Delivery',
			'config' => 'Config',
			'mark_up' => 'Mark Up',
			'sort' => 'Sort',
			'created_at' => 'Created At',
			'deleted_at' => 'Deleted At',
		];
	}

	public static function find(): PaymentMethodQuery
	{
		return new PaymentMethodQuery(get_called_class());
	}

	/**
	 * Gets query for [[Orders]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrders()
	{
		return $this->hasMany(Orders::class, ['payment_method_id' => 'payment_method_id']);
	}

	/**
	 * Gets query for [[PaymentMethodDeliveries]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPaymentMethodDeliveries()
	{
		return $this->hasMany(PaymentMethodDelivery::class, ['payment_method_id' => 'payment_method_id']);
	}

	/**
	 * Gets query for [[PaymentMethodTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPaymentMethodText()
	{
		return $this->hasOne(PaymentMethodText::class, ['payment_method_id' => 'payment_method_id'])
			->where(['payment_method_text.lang_id' => Lang::DEFAULT_LANG])
		;
	}

	/**
	 * Gets query for [[PaymentTransactions]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPaymentTransactions()
	{
		return $this->hasMany(PaymentTransaction::class, ['payment_method_id' => 'payment_method_id']);
	}

	/**
	 * Gets query for [[Site]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getSite()
	{
		return $this->hasOne(Site::class, ['site_id' => 'site_id']);
	}

	public function getPaymentGateway()
	{
		return $this->hasOne(PaymentGateway::class, ['payment_gateway_id' => 'payment_gateway_id']);
	}

	public function fields(): array
	{
		$out = [
			'payment_method_id',
			'title' => function (self $model) {
				return $model->paymentMethodText?->title;
			},
			'for_all_delivery',
			'mark_up',
			'sort',
		];

		if ($this->isRelationPopulated('paymentGateway')) {
			$out['gateway_alias'] = function (self $model) {
				return $model->paymentGateway->alias;
			};
		}

		return $out;
	}
}
