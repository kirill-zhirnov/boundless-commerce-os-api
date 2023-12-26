<?php

namespace app\modules\inventory\models;

use app\modules\system\models\Lang;
use Yii;

/**
 * This is the model class for table "inventory_option".
 *
 * @property int $option_id
 * @property string $category
 * @property string|null $alias
 * @property int $sort
 * @property string $created_at
 * @property string|null $deleted_at
 *
 * @property InventoryMovement[] $inventoryMovements
 * @property InventoryOptionText $inventoryOptionText
 */
class InventoryOption extends \yii\db\ActiveRecord
{
	const CATEGORY_CHANGE_QTY = 'changeQty';
	const CATEGORY_SYSTEM_CHANGE_QTY = 'systemChangeQty';

	const ALIAS_AVAILABLE_TO_RESERVE = 'availableToReserve';
	const ALIAS_AVAILABLE_TO_OUTSIDE = 'availableToOutside';
	const ALIAS_API_REQUEST = 'apiRequest';
	const ALIAS_OUTSIDE_TO_RESERVED = 'outsideToReserved';
	const ALIAS_RESERVED_TO_OUTSIDE = 'reservedToOutside';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'inventory_option';
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
			[['category', 'sort'], 'required'],
			[['category', 'alias'], 'string'],
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
			'option_id' => 'Option ID',
			'category' => 'Category',
			'alias' => 'Alias',
			'sort' => 'Sort',
			'created_at' => 'Created At',
			'deleted_at' => 'Deleted At',
		];
	}

	/**
	 * Gets query for [[InventoryMovements]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInventoryMovements()
	{
		return $this->hasMany(InventoryMovement::class, ['reason_id' => 'option_id']);
	}

	/**
	 * Gets query for [[InventoryOptionTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInventoryOptionText()
	{
		return $this->hasOne(InventoryOptionText::className(), ['option_id' => 'option_id'])
			->where(['inventory_option_text.lang_id' => Lang::DEFAULT_LANG]);
	}
}
