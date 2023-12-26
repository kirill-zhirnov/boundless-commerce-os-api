<?php

namespace app\modules\system\models;

use Yii;
use app\modules\catalog\models\FinalPrice;
use app\modules\inventory\models\InventoryPrice;
use app\modules\payment\models\PaymentTransaction;

/**
 * This is the model class for table "currency".
 *
 * @property int $currency_id
 * @property string $alias
 * @property int $code
 *
 * @property FinalPrice[] $finalPrices
 * @property InventoryPrice[] $inventoryPrices
 * @property PaymentTransaction[] $paymentTransactions
 */
class Currency extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'currency';
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
			[['alias', 'code'], 'required'],
			[['code'], 'default', 'value' => null],
			[['code'], 'integer'],
			[['alias'], 'string', 'max' => 3],
			[['alias'], 'unique'],
			[['code'], 'unique'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'currency_id' => 'Currency ID',
			'alias' => 'Alias',
			'code' => 'Code',
		];
	}

	/**
	 * Gets query for [[FinalPrices]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getFinalPrices()
	{
		return $this->hasMany(FinalPrice::class, ['currency_id' => 'currency_id']);
	}

	/**
	 * Gets query for [[InventoryPrices]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInventoryPrices()
	{
		return $this->hasMany(InventoryPrice::class, ['currency_id' => 'currency_id']);
	}

	/**
	 * Gets query for [[PaymentTransactions]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPaymentTransactions()
	{
		return $this->hasMany(PaymentTransaction::class, ['currency_id' => 'currency_id']);
	}
}
