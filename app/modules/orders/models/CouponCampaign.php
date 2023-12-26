<?php

namespace app\modules\orders\models;

use Yii;

/**
 * This is the model class for table "coupon_campaign".
 *
 * @property int $campaign_id
 * @property string $title
 * @property string|null $discount_type
 * @property float|null $discount_value
 * @property int|null $limit_usage_per_code
 * @property int|null $limit_usage_per_customer
 * @property float|null $min_order_amount
 * @property string|null $created_at
 * @property string|null $deleted_at
 *
 * @property CouponCode[] $couponCodes
 */
class CouponCampaign extends \yii\db\ActiveRecord
{
	const DISCOUNT_TYPE_FIXED = 'fixed';
	const DISCOUNT_TYPE_PERCENT = 'percent';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'coupon_campaign';
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
			[['title'], 'required'],
			[['discount_type'], 'string'],
			[['discount_value', 'min_order_amount'], 'number'],
			[['limit_usage_per_code', 'limit_usage_per_customer'], 'default', 'value' => null],
			[['limit_usage_per_code', 'limit_usage_per_customer'], 'integer'],
			[['created_at', 'deleted_at'], 'safe'],
			[['title'], 'string', 'max' => 255],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'campaign_id' => 'Campaign ID',
			'title' => 'Title',
			'discount_type' => 'Discount Type',
			'discount_value' => 'Discount Value',
			'limit_usage_per_code' => 'Limit Usage Per Code',
			'limit_usage_per_customer' => 'Limit Usage Per Customer',
			'min_order_amount' => 'Min Order Amount',
			'created_at' => 'Created At',
			'deleted_at' => 'Deleted At',
		];
	}

	/**
	 * Gets query for [[CouponCodes]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCouponCodes()
	{
		return $this->hasMany(CouponCode::class, ['campaign_id' => 'campaign_id']);
	}
}
