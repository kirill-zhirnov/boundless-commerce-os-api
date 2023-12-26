<?php

namespace app\modules\orders\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "order_prop".
 *
 * @property int $order_id
 * @property string|null $client_comment
 * @property string|null $custom_attrs
 *
 * @property Orders $order
 */
class OrderProp extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'order_prop';
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
			[['order_id'], 'default', 'value' => null],
			[['order_id'], 'integer'],
			[['client_comment'], 'string'],
			[['custom_attrs'], 'safe'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'order_id' => 'Order ID',
			'client_comment' => 'Client Comment',
			'custom_attrs' => 'Custom Attrs',
		];
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

	public function extendCustomAttrs(array $attrs)
	{
		$oldAttrs = $this->custom_attrs ?? [];

		$this->custom_attrs = ArrayHelper::merge($oldAttrs, $attrs);
		$this->save(false);
	}

	public function fields(): array
	{
		return [
			'client_comment',
			'custom_attrs'
		];
	}
}
