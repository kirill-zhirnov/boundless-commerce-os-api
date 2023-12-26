<?php

namespace app\modules\inventory\models;

use Yii;
use Lang;

/**
 * This is the model class for table "warehouse_text".
 *
 * @property int $warehouse_id
 * @property int $lang_id
 * @property string|null $title
 * @property string|null $address
 *
 * @property Lang $lang
 * @property Warehouse $warehouse
 */
class WarehouseText extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'warehouse_text';
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
			[['warehouse_id', 'lang_id'], 'required'],
			[['warehouse_id', 'lang_id'], 'default', 'value' => null],
			[['warehouse_id', 'lang_id'], 'integer'],
			[['title', 'address'], 'string'],
			[['lang_id', 'title'], 'unique', 'targetAttribute' => ['lang_id', 'title']],
			[['warehouse_id', 'lang_id'], 'unique', 'targetAttribute' => ['warehouse_id', 'lang_id']],
			[['lang_id'], 'exist', 'skipOnError' => true, 'targetClass' => Lang::class, 'targetAttribute' => ['lang_id' => 'lang_id']],
			[['warehouse_id'], 'exist', 'skipOnError' => true, 'targetClass' => Warehouse::class, 'targetAttribute' => ['warehouse_id' => 'warehouse_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'warehouse_id' => 'Warehouse ID',
			'lang_id' => 'Lang ID',
			'title' => 'Title',
			'address' => 'Address',
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
	 * Gets query for [[Warehouse]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getWarehouse()
	{
		return $this->hasOne(Warehouse::class, ['warehouse_id' => 'warehouse_id']);
	}
}
