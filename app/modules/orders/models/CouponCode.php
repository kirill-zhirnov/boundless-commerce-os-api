<?php

namespace app\modules\orders\models;

use Yii;

/**
 * This is the model class for table "coupon_code".
 *
 * @property int $code_id
 * @property int $campaign_id
 * @property string $code
 * @property string|null $created_at
 *
 * @property CouponCampaign $campaign
 * @property OrderDiscount[] $orderDiscounts
 */
class CouponCode extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'coupon_code';
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
			[['campaign_id', 'code'], 'required'],
			[['campaign_id'], 'default', 'value' => null],
			[['campaign_id'], 'integer'],
			[['code'], 'string'],
			[['created_at'], 'safe'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'code_id' => 'Code ID',
			'campaign_id' => 'Campaign ID',
			'code' => 'Code',
			'created_at' => 'Created At',
		];
	}

	/**
	 * Gets query for [[Campaign]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCampaign()
	{
		return $this->hasOne(CouponCampaign::class, ['campaign_id' => 'campaign_id']);
	}

	/**
	 * Gets query for [[OrderDiscounts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrderDiscounts()
	{
		return $this->hasMany(OrderDiscount::class, ['code_id' => 'code_id']);
	}
}
