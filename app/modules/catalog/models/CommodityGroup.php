<?php

namespace app\modules\catalog\models;

use Yii;
use app\modules\system\models\Lang;

/**
 * This is the model class for table "commodity_group".
 *
 * @property int $group_id
 * @property string|null $type
 * @property int|null $unit_id
 * @property bool $not_track_inventory
 * @property string $created_at
 * @property string|null $deleted_at
 * @property bool $yml_export
 * @property bool $is_default
 * @property string $vat
 *
 * @property Characteristic[] $characteristics
 * @property CommodityGroupText[] $commodityGroupTexts
 * @property Lang[] $langs
 * @property OneCPropertyCharacteristic[] $oneCPropertyCharacteristics
 * @property Product[] $products
 * @property UnitMeasurement $unit
 */
class CommodityGroup extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'commodity_group';
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
			[['type'], 'string'],
			[['unit_id'], 'default', 'value' => null],
			[['unit_id'], 'integer'],
			[['not_track_inventory', 'yml_export', 'is_default'], 'boolean'],
			[['created_at', 'deleted_at'], 'safe'],
			[['vat'], 'string', 'max' => 20],
			[['is_default'], 'unique']
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'group_id' => 'Group ID',
			'type' => 'Type',
			'unit_id' => 'Unit ID',
			'not_track_inventory' => 'Not Track Inventory',
			'created_at' => 'Created At',
			'deleted_at' => 'Deleted At',
			'yml_export' => 'Yml Export',
			'is_default' => 'Is Default',
			'vat' => 'Vat',
		];
	}

	/**
	 * Gets query for [[Characteristics]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristics()
	{
		return $this->hasMany(Characteristic::class, ['group_id' => 'group_id']);
	}

	/**
	 * Gets query for [[CommodityGroupTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCommodityGroupTexts()
	{
		return $this->hasMany(CommodityGroupText::class, ['group_id' => 'group_id']);
	}

	/**
	 * Gets query for [[Langs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLangs()
	{
		return $this->hasMany(Lang::class, ['lang_id' => 'lang_id'])->viaTable('commodity_group_text', ['group_id' => 'group_id']);
	}

	/**
	 * Gets query for [[Products]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProducts()
	{
		return $this->hasMany(Product::class, ['group_id' => 'group_id']);
	}

	/**
	 * Gets query for [[Unit]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getUnit()
	{
		return $this->hasOne(UnitMeasurement::className(), ['unit_id' => 'unit_id']);
	}

	public function fields(): array
	{
		$out = parent::fields();

		unset($out['unit_id'], $out['yml_export'], $out['vat'], $out['not_track_inventory'], $out['type'], $out['deleted_at'], $out['created_at']);

		if ($this->isRelationPopulated('commodityGroupTexts') && $this->commodityGroupTexts) {
			$out['title'] = function (self $model) {
				return $model->commodityGroupTexts[0]?->title;
			};
		};

		$out['track_inventory'] = function (self $model) {
			return !$model->not_track_inventory;
		};

		if ($this->isRelationPopulated('characteristics')) {
			$out['attributes'] = function (self $model) {
				return $model->characteristics;
			};
		}

		return $out;
	}
}
