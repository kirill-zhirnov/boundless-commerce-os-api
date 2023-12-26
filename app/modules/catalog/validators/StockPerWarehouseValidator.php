<?php

namespace app\modules\catalog\validators;

use app\modules\catalog\models\CommodityGroup;
use app\modules\catalog\models\Product;
use app\modules\inventory\models\Warehouse;
use app\modules\system\models\Setting;
use yii\validators\Validator;
use Yii;

class StockPerWarehouseValidator extends Validator
{
	public ?Product $product;
	public ?string $groupIdAttribute;
	public ?string $isInStockAttribute;

	public function validateAttribute($model, $attribute)
	{
		$stockPerWarehouse = $model->$attribute;
		$isInStock = isset($this->isInStockAttribute) ? $model->{$this->isInStockAttribute} : null;
		$groupId = isset($this->product) ? $this->product->group_id : null;

		if (isset($this->groupIdAttribute, $model->{$this->groupIdAttribute})) {
			$groupId = $model->{$this->groupIdAttribute};
		}

		/** @var CommodityGroup $commodityGroup */
		$commodityGroup = CommodityGroup::findOne($groupId);

		if (!$groupId || !$commodityGroup) {
			$model->$attribute = null;
			$model->{$this->isInStockAttribute} = null;
			return;
		}

		$globalShallTrackInventory = Setting::shallTrackInventory();

		if ($commodityGroup->not_track_inventory || !$globalShallTrackInventory) {
			if (isset($stockPerWarehouse)) {
				$this->addError($model, $attribute, Yii::t('app', 'Inventory tracking is disabled in Commodity Group "{group}". Please use is_in_stock attribute.', [
					'group' => $commodityGroup->commodityGroupTexts[0]->title
				]));
				return;
			}
		} else {
			if (isset($isInStock)) {
				$this->addError($model, $attribute, Yii::t('app', 'Inventory tracking is enabled in Commodity Group "{group}". Please use stock_per_warehouse attribute.', [
					'group' => $commodityGroup->commodityGroupTexts[0]->title
				]));
				return;
			}
		}

		if (isset($stockPerWarehouse)) {
			if (!is_array($stockPerWarehouse)) {
				$this->addError($model, $attribute, Yii::t('app', 'Should be an object with one of the following keys: "total" or "<warehouseId: int>".'));
				return;
			}

			$filteredValue = [];
			foreach ($stockPerWarehouse as $key => $value) {
				if ($key !== 'total' && isset($stockPerWarehouse['total'])) {
					$this->addError(
						$model,
						$attribute,
						Yii::t('app', 'If key "total" is specified, warehouse keys cant be specified.')
					);
					return;
				}

				if ($key !== 'total') {
					$warehouse = Warehouse::find()
						->where(['warehouse_id' => intval($key), 'deleted_at' => null])
						->one()
					;

					if (!$warehouse) {
						$this->addError(
							$model,
							$attribute,
							Yii::t('app', 'Key "{key}" is wrong: it should be an ID of a warehouse, but there is no warehouses with such ID', [
								'key' => $key
							])
						);
						return;
					}
				}

				if (!preg_match('/^\d+$/', $value)) {
					$this->addError(
						$model,
						$attribute,
						Yii::t('app', 'Wrong value for key "{key}": only digit allowed.', [
							'key' => $key
						])
					);
					return;
				}

				$filteredValue[$key] = intval($value);
			}

			if (isset($filteredValue['total'])) {
				$hasActiveWarehouses = Warehouse::find()->where(['deleted_at' => null])->count();
				if ($hasActiveWarehouses == 0) {
					$this->addError($model, $attribute, Yii::t('app', 'There is no active warehouses.'));
					return;
				}
			}

			$model->$attribute = $filteredValue;
		}

		if (isset($isInStock)) {
			if (!is_bool($isInStock) && !in_array($isInStock, ['0', '1'])) {
				$this->addError($model, $this->isInStockAttribute, Yii::t('app', 'Should be a boolean or "0" or "1".'));
				return;
			}

			if (!is_bool($isInStock)) {
				$isInStock = $isInStock == '1';
			}

			$model->{$this->isInStockAttribute} = $isInStock;
		}
	}
}
