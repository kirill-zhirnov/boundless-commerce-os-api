<?php

namespace app\modules\orders\models;

use Yii;

/**
 * This is the model class for table "order_discount".
 *
 * @property int $discount_id
 * @property int $order_id
 * @property string|null $title
 * @property string|null $discount_type
 * @property float|null $value
 * @property string|null $source
 * @property int|null $code_id
 * @property string|null $created_at
 *
 * @property CouponCode $code
 * @property Orders $order
 */
class OrderDiscount extends \yii\db\ActiveRecord
{
	const  SOURCE_MANUAL = 'manual';
	const  SOURCE_COUPON = 'coupon';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'order_discount';
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
			[['order_id'], 'required'],
			[['order_id', 'code_id'], 'default', 'value' => null],
			[['order_id', 'code_id'], 'integer'],
			[['discount_type', 'source'], 'string'],
			[['value'], 'number'],
			[['created_at'], 'safe'],
			[['title'], 'string', 'max' => 255]
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'discount_id' => 'Discount ID',
			'order_id' => 'Order ID',
			'title' => 'Title',
			'discount_type' => 'Discount Type',
			'value' => 'Value',
			'source' => 'Source',
			'code_id' => 'Code ID',
			'created_at' => 'Created At',
		];
	}

	/**
	 * Gets query for [[Code]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCode()
	{
		return $this->hasOne(CouponCode::class, ['code_id' => 'code_id']);
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

	public function fields(): array
	{
		return [
			'discount_id',
			'title',
			'discount_type',
			'value',
			'source',
			'code_id',
			'created_at'
		];
	}
}
