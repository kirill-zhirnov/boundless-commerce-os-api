<?php

namespace app\modules\payment\models;

use Yii;
use app\modules\system\models\Lang;

/**
 * This is the model class for table "payment_method_text".
 *
 * @property int $payment_method_id
 * @property int $lang_id
 * @property string|null $title
 *
 * @property Lang $lang
 * @property PaymentMethod $paymentMethod
 */
class PaymentMethodText extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'payment_method_text';
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
			[['payment_method_id', 'lang_id'], 'required'],
			[['payment_method_id', 'lang_id'], 'default', 'value' => null],
			[['payment_method_id', 'lang_id'], 'integer'],
			[['title'], 'string', 'max' => 255],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'payment_method_id' => 'Payment Method ID',
			'lang_id' => 'Lang ID',
			'title' => 'Title',
		];
	}

	/**
	 * Gets query for [[Lang]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLang()
	{
		return $this->hasOne(Lang::class, ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[PaymentMethod]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPaymentMethod()
	{
		return $this->hasOne(PaymentMethod::class, ['payment_method_id' => 'payment_method_id']);
	}
}
