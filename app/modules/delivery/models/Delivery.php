<?php

namespace app\modules\delivery\models;

use app\modules\delivery\activeQueries\DeliveryQuery;
use app\modules\system\models\Lang;
use Yii;
use app\modules\system\models\Site;
use app\modules\user\models\Person;
use app\modules\orders\models\OrderServiceDelivery;

/**
 * This is the model class for table "delivery".
 *
 * @property int $delivery_id
 * @property string|null $alias
 * @property string $created_at
 * @property string|null $deleted_at
 * @property int|null $shipping_id
 * @property string|null $shipping_config
 * @property int|null $location_shipping_id
 * @property float|null $free_shipping_from
 * @property string|null $calc_method
 * @property string|null $img
 * @property string $status
 * @property int|null $created_by
 * @property string|null $tax
 * @property string|null $mark_up
 *
 * @property Person $createdBy
 * @property DeliverySite $deliverySite
 * @property DeliveryText $deliveryText
 * @property OrderServiceDelivery[] $orderServiceDeliveries
 * @property Site[] $sites
 * @property VwShipping $vwShipping
 */
class Delivery extends \yii\db\ActiveRecord
{
	const CALC_METHOD_SHIPPING_SERVICE = 'byShippingService';
	const CALC_METHOD_OWN_RATES = 'byOwnRates';
	const CALC_METHOD_SINGLE = 'single';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'delivery';
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
			[['alias', 'calc_method', 'status'], 'string'],
			[['created_at', 'deleted_at', 'shipping_config'], 'safe'],
			[['shipping_id', 'location_shipping_id', 'created_by'], 'default', 'value' => null],
			[['shipping_id', 'location_shipping_id', 'created_by'], 'integer'],
			[['free_shipping_from'], 'number'],
			[['img', 'mark_up'], 'string', 'max' => 255],
			[['tax'], 'string', 'max' => 30],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'delivery_id' => 'Delivery ID',
			'alias' => 'Alias',
			'created_at' => 'Created At',
			'deleted_at' => 'Deleted At',
			'shipping_id' => 'Shipping ID',
			'shipping_config' => 'Shipping Config',
			'location_shipping_id' => 'Location Shipping ID',
			'free_shipping_from' => 'Free Shipping From',
			'calc_method' => 'Calc Method',
			'img' => 'Img',
			'status' => 'Status',
			'created_by' => 'Created By',
			'tax' => 'Tax',
			'mark_up' => 'Mark Up',
		];
	}

	public function getCreatedBy()
	{
		return $this->hasOne(Person::class, ['person_id' => 'created_by']);
	}

	public function getVwShipping()
	{
		return $this->hasOne(VwShipping::class, ['shipping_id' => 'shipping_id'])
			->where(['vw_shipping.lang_id' => Lang::DEFAULT_LANG])
		;
	}

	/**
	 * Gets query for [[DeliverySite]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getDeliverySite()
	{
		return $this->hasOne(DeliverySite::class, ['delivery_id' => 'delivery_id'])
			->where(['delivery_site.site_id' => Site::DEFAULT_SITE])
		;
	}

	/**
	 * Gets query for [[DeliveryText]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getDeliveryText()
	{
		return $this->hasOne(DeliveryText::class, ['delivery_id' => 'delivery_id'])
			->where(['delivery_text.lang_id' => Lang::DEFAULT_LANG])
		;
	}

	/**
	 * Gets query for [[OrderServiceDeliveries]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrderServiceDeliveries()
	{
		return $this->hasMany(OrderServiceDelivery::class, ['delivery_id' => 'delivery_id']);
	}

	/**
	 * Gets query for [[Sites]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getSites()
	{
		return $this->hasMany(Site::class, ['site_id' => 'site_id'])->viaTable('delivery_site', ['delivery_id' => 'delivery_id']);
	}

	public static function find(): DeliveryQuery
	{
		return new DeliveryQuery(get_called_class());
	}

	public function isCalcMethodSingle(): bool
	{
		return $this->calc_method === self::CALC_METHOD_SINGLE;
	}

	public function isCalcMethodByShippingService(): bool
	{
		return $this->calc_method === self::CALC_METHOD_SHIPPING_SERVICE;
	}

	public function isRequiredShippingAddress(): bool
	{
		if ($this->isCalcMethodByShippingService() && $this->vwShipping?->isSelfPickup()) {
			return false;
		}

		return true;
	}

	public function fields(): array
	{
		$out = [
			'delivery_id',
			'title' => function (self $model) {
				if ($model->isRelationPopulated('deliveryText')) {
					return $model->deliveryText->title;
				}
			},
			'description' => function (self $model) {
				if ($model->isRelationPopulated('deliveryText')) {
					return $model->deliveryText->description;
				}
			},
			'alias',
			'img',
			'shipping_id',
			'shipping_config',
			'free_shipping_from',
			'calc_method',
			'created_at'
		];

		if ($this->isRelationPopulated('vwShipping')) {
			$out['shipping'] = function (self $model) {
				return $model->vwShipping;
			};
		}

		return $out;
	}
}
