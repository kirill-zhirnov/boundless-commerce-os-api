<?php

namespace app\modules\inventory\models;

use Yii;

/**
 * This is the model class for table "vw_track_inventory".
 *
 * @property int|null $item_id
 * @property bool|null $track_inventory
 */
class VwTrackInventory extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'vw_track_inventory';
	}

	/**
	 * @return \yii\db\Connection the database connection used by this AR class.
	 */
	public static function getDb()
	{
		return Yii::$app->get('instanceDb');
	}

	public static function primaryKey()
	{
		return ['item_id'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['item_id'], 'default', 'value' => null],
			[['item_id'], 'integer'],
			[['track_inventory'], 'boolean'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'item_id' => 'Item ID',
			'track_inventory' => 'Track Inventory',
		];
	}
}
