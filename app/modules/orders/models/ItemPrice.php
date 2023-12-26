<?php

namespace app\modules\orders\models;

use Yii;
use app\modules\catalog\models\Price;

/**
 * This is the model class for table "item_price".
 *
 * @property int $item_price_id
 * @property int|null $price_id
 * @property float|null $basic_price
 * @property float|null $final_price
 * @property float|null $discount_amount
 * @property float|null $discount_percent
 *
 * @property BasketItem[] $basketItems
 * @property OrderService[] $orderServices
 * @property Price $price
 * @property ReserveItem[] $reserveItems
 */
class ItemPrice extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'item_price';
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
			[['price_id'], 'default', 'value' => null],
			[['price_id'], 'integer'],
			[['basic_price', 'final_price', 'discount_amount', 'discount_percent'], 'number'],

		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'item_price_id' => 'Item Price ID',
			'price_id' => 'Price ID',
			'basic_price' => 'Basic Price',
			'final_price' => 'Final Price',
			'discount_amount' => 'Discount Amount',
			'discount_percent' => 'Discount Percent',
		];
	}

	public function calcUnfilled()
	{
		if (!$this->final_price) {
			$this->final_price = $this->basic_price;
		}

		if ($this->basic_price != $this->final_price && !$this->discount_percent && !$this->discount_amount) {
			$this->discount_amount = bcsub($this->basic_price, $this->final_price);
		}
	}

	public function saveSinglePrice($price)
	{
		$this->basic_price = $price;
		$this->final_price = $price;
		$this->discount_amount = null;
		$this->discount_percent = null;

		if (!$this->save(false)) {
			throw new \RuntimeException('cannot save single price ' . print_r($this->getErrors(), 1));
		}
	}

	/**
	 * Gets query for [[BasketItems]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getBasketItems()
	{
		return $this->hasMany(BasketItem::class, ['item_price_id' => 'item_price_id']);
	}

	/**
	 * Gets query for [[OrderServices]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrderServices()
	{
		return $this->hasMany(OrderService::className(), ['item_price_id' => 'item_price_id']);
	}

	/**
	 * Gets query for [[Price]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPrice()
	{
		return $this->hasOne(Price::class, ['price_id' => 'price_id']);
	}

	/**
	 * Gets query for [[ReserveItems]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getReserveItems()
	{
		return $this->hasMany(ReserveItem::class, ['item_price_id' => 'item_price_id']);
	}

	public static function makeByFinalPrice(array|null $attrs): self
	{
		$row = new self();

		if (!is_null($attrs)) {
			if (isset($attrs['value'], $attrs['old'])) {
				$row->final_price = $attrs['value'];
				$row->basic_price = $attrs['old'];
			} else if (isset($attrs['value'])) {
				$row->basic_price = $attrs['value'];
			}

			if (isset($attrs['price_id'])) {
				$row->price_id = $attrs['price_id'];
			}
		}

		return $row;
	}
}
