<?php

namespace app\modules\delivery\models;

use Yii;

/**
 * This is the model class for table "vw_shipping".
 *
 * @property int|null $shipping_id
 * @property string|null $alias
 * @property string|null $settings
 * @property string|null $shipping_title
 * @property int|null $lang_id
 */
class VwShipping extends \yii\db\ActiveRecord
{
	const ALIAS_SELF_PICKUP = 'selfPickup';

	public static function tableName()
	{
		return 'vw_shipping';
	}

	public static function primaryKey()
	{
		return ['shipping_id'];
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
			[['shipping_id', 'lang_id'], 'default', 'value' => null],
			[['shipping_id', 'lang_id'], 'integer'],
			[['settings'], 'safe'],
			[['alias'], 'string', 'max' => 20],
			[['shipping_title'], 'string', 'max' => 100],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'shipping_id' => 'Shipping ID',
			'alias' => 'Alias',
			'settings' => 'Settings',
			'shipping_title' => 'Shipping Title',
			'lang_id' => 'Lang ID',
		];
	}

	public function fields(): array
	{
		return [
			'shipping_id',
			'shipping_title',
			'alias',
			'settings'
		];
	}

	public function isSelfPickup(): bool
	{
		return $this->alias === self::ALIAS_SELF_PICKUP;
	}
}
