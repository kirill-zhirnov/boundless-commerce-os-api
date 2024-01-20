<?php

namespace app\modules\catalog\activeQueries;

use app\modules\catalog\models\FinalPrice;
use app\modules\catalog\models\PointSale;
use app\modules\catalog\models\Price;
use app\modules\system\models\Lang;
use app\modules\user\models\Person;
use yii\db\ActiveQuery;
use yii\db\Expression;
use Yii;
use yii\web\User;

class ProductQuery extends ActiveQuery
{
	public function addInventoryItemSelect(): self
	{
		$this
			->addSelect('inventory_item.item_id as __item_id')
			->innerJoinWith('inventoryItem')
			->with('inventoryItem.vwTrackInventory')
		;

		return $this;
	}

	public function withFinalPrices(): self
	{
		$this
			->with(['inventoryItem.finalPrices' => function(ActiveQuery $query) {
				FinalPrice::addFinalPricesSelect($query);
			}])
			->with(['inventoryItem.finalPrices.currency', 'inventoryItem.finalPrices.price'])
		;
		return $this;
	}

//	public function addProductPriceSelect(): self
//	{
//		$this
//			->addSelect(new Expression("
//				json_build_object(
//					'value', final_price.value,
//					'min', final_price.min,
//					'max', final_price.max,
//					'old', final_price.old,
//					'old_min', final_price.old_min,
//					'old_max', final_price.old_max,
//					'currency_alias', currency.alias
//				) AS __product_price"))
//			->leftJoin('final_price', 'final_price.item_id = inventory_item.item_id and final_price.point_id = :pointId', [
//				'pointId' => PointSale::DEFAULT_POINT
//			])
//			->leftJoin('price', 'final_price.price_id = price.price_id')
//			->leftJoin('currency', 'currency.currency_id = final_price.currency_id')
//			->andWhere('price.alias = :priceAlias or final_price.point_id is null', [':priceAlias' => Price::ALIAS_SELLING_PRICE])
//		;
//
//		return $this;
//	}

	public function addProductImagesSelect($withTags = true): self
	{
		$this
			->with(['productImages' => function (ActiveQuery $query) {
				$query->orderBy(['product_image.sort' => SORT_ASC]);
			}])
			->with(['productImages.image' => function (ActiveQuery $query) {
				$query->where(['image.deleted_at' => null]);
			}])
			->with(['productImages.productImageTexts' => function (ActiveQuery $query) {
				$query->where(['product_image_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
		;

		if ($withTags) {
			$this->with(['productImages.imageTags']);
		}

		return $this;
	}
}
