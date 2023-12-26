<?php

namespace app\modules\delivery\models;

use Yii;
use app\modules\system\models\Site;
use app\modules\payment\models\PaymentMethod;
use app\modules\payment\models\PaymentMethodDelivery;

/**
 * This is the model class for table "delivery_site".
 *
 * @property int $delivery_site_id
 * @property int $site_id
 * @property int $delivery_id
 * @property int $sort
 *
 * @property Delivery $delivery
 * @property DeliveryCity[] $deliveryCities
 * @property DeliveryCountry[] $deliveryCountries
 * @property DeliveryExcludeCity[] $deliveryExcludeCities
 * @property PaymentMethodDelivery[] $paymentMethodDeliveries
 * @property PaymentMethod[] $paymentMethods
 * @property Site $site
 */
class DeliverySite extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'delivery_site';
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
			[['site_id', 'delivery_id', 'sort'], 'required'],
			[['site_id', 'delivery_id', 'sort'], 'default', 'value' => null],
			[['site_id', 'delivery_id', 'sort'], 'integer'],
			[['site_id', 'delivery_id'], 'unique', 'targetAttribute' => ['site_id', 'delivery_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'delivery_site_id' => 'Delivery Site ID',
			'site_id' => 'Site ID',
			'delivery_id' => 'Delivery ID',
			'sort' => 'Sort',
		];
	}

	/**
	 * Gets query for [[Delivery]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getDelivery()
	{
		return $this->hasOne(Delivery::class, ['delivery_id' => 'delivery_id']);
	}

	/**
	 * Gets query for [[DeliveryCities]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getDeliveryCities()
	{
		return $this->hasMany(DeliveryCity::className(), ['delivery_site_id' => 'delivery_site_id']);
	}

	/**
	 * Gets query for [[DeliveryCountries]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getDeliveryCountries()
	{
		return $this->hasMany(DeliveryCountry::className(), ['delivery_site_id' => 'delivery_site_id']);
	}

	/**
	 * Gets query for [[DeliveryExcludeCities]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getDeliveryExcludeCities()
	{
		return $this->hasMany(DeliveryExcludeCity::className(), ['delivery_site_id' => 'delivery_site_id']);
	}

	/**
	 * Gets query for [[PaymentMethodDeliveries]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPaymentMethodDeliveries()
	{
		return $this->hasMany(PaymentMethodDelivery::class, ['delivery_site_id' => 'delivery_site_id']);
	}

	/**
	 * Gets query for [[PaymentMethods]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPaymentMethods()
	{
		return $this->hasMany(PaymentMethod::class, ['payment_method_id' => 'payment_method_id'])->viaTable('payment_method_delivery', ['delivery_site_id' => 'delivery_site_id']);
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
}
