<?php

namespace app\modules\orders\components\orderItems;

use app\modules\inventory\models\InventoryItem;
use app\modules\orders\components\TotalCalculator;
use app\modules\orders\models\ItemPrice;

interface ProviderInterface
{
	public function addItem(InventoryItem $item, int $qty, ItemPrice $price);

	public function getItems(array $filter = null);

	public function bulkSetQty(array $items);

	public function rmItems(array $itemIds);

	public function getTotalCalculator(): TotalCalculator;
}
