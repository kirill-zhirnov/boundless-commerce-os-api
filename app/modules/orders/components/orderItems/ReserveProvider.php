<?php

namespace app\modules\orders\components\orderItems;

use app\modules\inventory\models\InventoryItem;
use app\modules\orders\components\TotalCalculator;
use app\modules\orders\models\ItemPrice;
use app\modules\orders\models\Reserve;
use app\modules\orders\models\Orders;
use app\modules\orders\models\ReserveItem;
use app\modules\system\models\Lang;
use yii\db\ActiveQuery;
use app\modules\system\models\Setting;

class ReserveProvider extends BasicProvider implements ProviderInterface
{
	protected Reserve $reserve;

	public function __construct(Orders $order)
	{
		$this->order = $order;
		$this->reserve = $order->reserve;
	}

	/**
	 * @param array|null $filter
	 * @return ReserveItem[]
	 */
	public function getItems(array $filter = null): array
	{
		$query = ReserveItem::find()
			->innerJoinWith('vwItem')
			->with(['itemPrice'])
			->with(['vwItem.labels' => function (ActiveQuery $query) {
				$query->where(['label.deleted_at' => null]);
			}])
			->with(['vwItem.labels.labelTexts' => function (ActiveQuery $query) {
				$query->where(['label_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->where([
				'reserve_item.reserve_id' => $this->reserve->reserve_id,
			])
			->orderBy(['reserve_item.reserve_item_id' => SORT_ASC])
		;

		if ($filter) {
			if ($filter['item_id']) {
				$query->andWhere(['reserve_item.item_id' => $filter['item_id']]);
			}

			if ($filter['product_id']) {
				$query->andWhere(['vw_inventory_item.product_id' => $filter['product_id']]);
			}

			if ($filter['variant_id']) {
				$query->andWhere(['vw_inventory_item.variant_id' => $filter['variant_id']]);
			}

			if ($filter['custom_item_id']) {
				$query->andWhere(['vw_inventory_item.custom_item_id' => $filter['custom_item_id']]);
			}
		}

		return $query->all();
	}

	public function getTotalCalculator(): TotalCalculator
	{
		$calculator = new TotalCalculator();
		$this->populateTotalCalculator($calculator);

		return $calculator;
	}

	public function addItem(InventoryItem $item, int $qty, ItemPrice $price)
	{
		throw new \RuntimeException('ReserveProvider.addItem isnt implemented');
	}

	public function bulkSetQty(array $items)
	{
		throw new \RuntimeException('ReserveProvider.bulkSetQty isnt implemented');
	}

	public function rmItems(array $itemIds)
	{
		throw new \RuntimeException('ReserveProvider.rmItems isnt implemented');
	}
}
