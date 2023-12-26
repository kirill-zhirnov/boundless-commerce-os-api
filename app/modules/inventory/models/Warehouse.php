<?php

namespace app\modules\inventory\models;

use Yii;
use app\modules\system\models\Lang;

/**
 * This is the model class for table "warehouse".
 *
 * @property int $warehouse_id
 * @property int $sort
 * @property string $created_at
 * @property string|null $deleted_at
 *
 * @property InventoryLocation $inventoryLocation
 * @property Lang[] $langs
 * @property WarehouseText[] $warehouseTexts
 * @property WarehouseText $warehouseTextDefault
 */
class Warehouse extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'warehouse';
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
			[['sort'], 'required'],
			[['sort'], 'default', 'value' => null],
			[['sort'], 'integer'],
			[['created_at', 'deleted_at'], 'safe'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'warehouse_id' => 'Warehouse ID',
			'sort' => 'Sort',
			'created_at' => 'Created At',
			'deleted_at' => 'Deleted At',
		];
	}

	/**
	 * Gets query for [[InventoryLocation]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInventoryLocation()
	{
		return $this->hasOne(InventoryLocation::class, ['warehouse_id' => 'warehouse_id']);
	}

	/**
	 * Gets query for [[Langs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLangs()
	{
		return $this->hasMany(Lang::class, ['lang_id' => 'lang_id'])->viaTable('warehouse_text', ['warehouse_id' => 'warehouse_id']);
	}

	/**
	 * Gets query for [[WarehouseTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getWarehouseTexts()
	{
		return $this->hasMany(WarehouseText::class, ['warehouse_id' => 'warehouse_id']);
	}

	public function warehouseTextDefault()
	{
		return $this->hasOne(WarehouseText::class, ['warehouse_id' => 'warehouse_id'])
			->andWhere(['warehouse_text.lang_id' => Lang::DEFAULT_LANG])
		;
	}
}
