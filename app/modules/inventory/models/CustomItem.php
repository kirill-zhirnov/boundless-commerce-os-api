<?php

namespace app\modules\inventory\models;

use Yii;

/**
 * This is the model class for table "custom_item".
 *
 * @property int $custom_item_id
 * @property string $title
 * @property float $price
 *
 * @property InventoryItem $inventoryItem
 */
class CustomItem extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'custom_item';
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
			[['title', 'price'], 'required'],
			[['price'], 'number'],
			[['title'], 'string', 'max' => 255],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'custom_item_id' => 'Custom Item ID',
			'title' => 'Title',
			'price' => 'Price',
		];
	}

	/**
	 * Gets query for [[InventoryItem]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInventoryItem()
	{
		return $this->hasOne(InventoryItem::class, ['custom_item_id' => 'custom_item_id']);
	}
}
