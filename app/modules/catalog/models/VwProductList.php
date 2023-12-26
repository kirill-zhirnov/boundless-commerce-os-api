<?php

namespace app\modules\catalog\models;

use app\helpers\Util;
use app\modules\system\models\Setting;
use Yii;

/**
 * This is the model class for table "vw_product_list".
 *
 * @property int|null $product_id
 * @property string|null $sku
 * @property string|null $title
 * @property string|null $url_key
 * @property bool|null $has_variants
 * @property array| $product_type
 * @property string|null $manufacturer
 * @property string|null $price_alias
 * @property string|null $price
 * @property array|null $props
 * @property array $default_category
 * @property string|null $images
 * @property string|null $variants
 * @property string|null $labels
 * @property float|null $sort_price
 * @property int|null $sort_in_stock
 * @property string|null $status
 * @property string|null $deleted_at
 */
class VwProductList extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'vw_product_list';
	}

	/**
	 * {@inheritdoc}
	 */
	public static function primaryKey()
	{
		return ['product_id'];
	}

	/**
	 * @return \yii\db\Connection the database connection used by this AR class.
	 */
	public static function getDb()
	{
		return Yii::$app->get('instanceDb');
	}

	/**
	 * @inheritdoc
	 *
	 * @return array
	 */
	public function fields(): array
	{
		$basic = parent::fields();
		unset($basic['manufacturer_id'], $basic['point_id']);

		$fields = [];
		foreach ($basic as $key => $value) {
			$fields[$key] = $value;

			if ($key === 'item_id') {
				$fields['in_stock'] = fn () => $this->isInStock();
			}
		}

		$fields['manufacturer'] = function (self $model) {
			if (is_null($model->manufacturer['manufacturer_id'])) {
				return null;
			} else {
				return $model->manufacturer;
			}
		};

		$fields['price'] = function (self $model) {
			if (is_null($model->price_alias)) {
				return null;
			} else {
				return $this->price;
			}
		};

		$fields['default_category'] = function (self $model) {
			if (is_null($model->default_category['category_id'])) {
				return null;
			} else {
				return $model->default_category;
			}
		};

		$fields['product_type'] = function (self $model) {
			if (is_null($model->product_type['group_id'])) {
				return null;
			} else {
				return $model->product_type;
			}
		};

		$fields['images'] = function (self $model) {
			return Util::sqlAggArr2Objects($model->images);
		};

		$fields['labels'] = function (self $model) {
			return Util::sqlAggArr2Objects($model->labels);
		};

		return $fields;
	}

	public function isInStock(): bool|null
	{
		$shallTrackInventorySetting = Setting::shallTrackInventory();
		if (!$shallTrackInventorySetting) {
			return true;
		}

		if (!$this->product_type || !$this->props) {
			return null;
		}

		return $this->props['available_qty'] > 0;
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['product_id', 'sort_in_stock'], 'default', 'value' => null],
			[['product_id', 'sort_in_stock'], 'integer'],
			[['sku', 'title', 'url_key', 'price_alias', 'status'], 'string'],
			[['has_variants'], 'boolean'],
			[['commodity_group', 'manufacturer', 'price', 'props', 'images', 'deleted_at','variants','labels'], 'safe'],
			[['sort_price'], 'number'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'product_id' => 'Product ID',
			'sku' => 'Sku',
			'title' => 'Title',
			'url_key' => 'Url Key',
			'has_variants' => 'Has Variants',
			'commodity_group' => 'Commodity Group',
			'manufacturer' => 'Manufacturer',
			'price_alias' => 'Price Alias',
			'price' => 'Price',
			'props' => 'Props',
			'images' => 'Images',
			'sort_price' => 'Sort Price',
			'sort_in_stock' => 'Sort In Stock',
			'status' => 'Status',
			'deleted_at' => 'Deleted At',
		];
	}
}
