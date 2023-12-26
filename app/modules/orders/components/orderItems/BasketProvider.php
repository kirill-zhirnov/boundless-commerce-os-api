<?php

namespace app\modules\orders\components\orderItems;

use app\modules\orders\components\TotalCalculator;
use app\modules\orders\models\BasketItem;
use app\modules\orders\models\Orders;
use app\modules\system\models\Lang;
use app\modules\inventory\models\InventoryItem;
use app\modules\orders\models\Basket;
use app\modules\orders\models\ItemPrice;
use yii\db\ActiveQuery;
use yii\db\Expression;

class BasketProvider extends BasicProvider implements ProviderInterface
{
	public function __construct(
		protected Basket $basket,
	)
	{}

	public function addItem(InventoryItem $item, int $qty, ItemPrice $price)
	{
		$price->calcUnfilled();

		$this->getDb()
			->createCommand('
				select basket_add_item(:basketId, :itemId, :qty, :priceId, :basicPrice, :finalPrice, :discountAmount, :discountPercent)
			')
			->bindValues([
				'basketId' => $this->basket->basket_id,
				'itemId' => $item->item_id,
				'qty' => $qty,
				'priceId' => $price->price_id ?: null,
				'basicPrice' => $price->basic_price,
				'finalPrice' => $price->final_price,
				'discountAmount' => $price->discount_amount,
				'discountPercent' => $price->discount_percent
			])
			->execute()
		;

		$this->reCalcOrderTotal();
	}

	/**
	 * @param array|null $filter
	 * @return BasketItem[]
	 */
	public function getItems(array $filter = null): array
	{
		$query = BasketItem::find()
			->innerJoinWith('vwItem')
			->with(['itemPrice'])
			->with(['vwItem.labels' => function(ActiveQuery $query) {
				$query->where(['label.deleted_at' => null]);
			}])
			->with(['vwItem.labels.labelTexts' => function(ActiveQuery $query) {
				$query->where(['label_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->where([
				'basket_item.basket_id' => $this->basket->basket_id,
				'basket_item.deleted_at' => null
			])
			->orderBy(['basket_item.basket_item_id' => SORT_ASC])
		;

		if ($filter) {
			if ($filter['item_id']) {
				$query->andWhere(['basket_item.item_id' => $filter['item_id']]);
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

	public function bulkSetQty(array $items)
	{
		foreach ($items as $item) {
			/** @var BasketItem  $basketItem */
			$basketItem = BasketItem::find()
				->where([
					'basket_id' => $this->basket->basket_id,
					'item_id' => $item['item_id'],
				])
				->one()
			;

			if (!$basketItem) {
				continue;
			}

			$qty = intval($item['qty']);

			if ($qty > 0) {
				$basketItem->qty = $qty;
				if (!$basketItem->save()) {
					throw new \RuntimeException('Cannot save basketItem, id: ' . $basketItem->basket_item_id . ':' . print_r($basketItem->getErrors(), 1));
				}
			} else {
				$basketItem->markDeleted();
			}
		}

		$this->reCalcOrderTotal();
	}

	public function rmItems(array $itemIds)
	{
		BasketItem::updateAll(['deleted_at' => new Expression('now()')], [
			'basket_id' => $this->basket->basket_id,
			'item_id' => $itemIds
		]);

		$this->reCalcOrderTotal();
	}

	public function getTotalCalculator(): TotalCalculator
	{
		$calculator = new TotalCalculator();
		$this->populateTotalCalculator($calculator);

		return $calculator;
	}

	public function setOrder(Orders $order): BasketProvider
	{
		$this->order = $order;
		return $this;
	}
}
