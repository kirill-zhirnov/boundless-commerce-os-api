<?php

namespace app\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "product_prop".
 *
 * @property int $product_id
 * @property int $available_qty
 * @property int $reserved_qty
 * @property string|null $layout
 * @property int|null $country_of_origin
 * @property string|null $extra
 * @property string $size
 * @property string|null $characteristic
 * @property string $tax_status
 * @property int|null $tax_class_id
 * @property array|null $arbitrary_data
 *
 * @property Product $product
 */
class ProductProp extends \yii\db\ActiveRecord
{
	const TAX_STATUS_TAXABLE = 'taxable';
	const TAX_STATUS_NONE = 'none';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'product_prop';
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
			[['product_id'], 'required'],
			[['product_id', 'available_qty', 'reserved_qty', 'country_of_origin'], 'default', 'value' => null],
			[['product_id', 'available_qty', 'reserved_qty', 'country_of_origin', 'tax_class_id'], 'integer'],
			[['extra', 'size', 'characteristic', 'tax_status'], 'safe'],
			[['layout'], 'string', 'max' => 255],
			[['product_id'], 'unique'],
			[['arbitrary_data'], 'safe'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'product_id' => 'Product ID',
			'available_qty' => 'Available Qty',
			'reserved_qty' => 'Reserved Qty',
			'layout' => 'Layout',
			'country_of_origin' => 'Country Of Origin',
			'extra' => 'Extra',
			'size' => 'Size',
			'characteristic' => 'Characteristic',
		];
	}

	/**
	 * Gets query for [[Product]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProduct()
	{
		return $this->hasOne(Product::class, ['product_id' => 'product_id']);
	}

	public function fields(): array
	{
		$out = parent::fields();
		unset($out['product_id'], $out['layout'], $out['characteristic']);

		$out['attr_values'] = fn () => $this->characteristic;
		$out['size'] = function ($model) {
			if (empty($model->size)) {
				return null;
			} else {
				return $model->size;
			}
		};

		return $out;
	}
}
