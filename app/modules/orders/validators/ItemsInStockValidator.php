<?php

namespace app\modules\orders\validators;

use app\modules\orders\components\OrderItems;
use app\modules\orders\models\Basket;
use RuntimeException;
use yii\validators\Validator;
use Yii;

class ItemsInStockValidator extends Validator
{
	public ?Basket $basket;

	public ?array $desiredQty;

	public function validateAttribute($model, $attribute)
	{
		if (!isset($this->basket)) {
			throw new RuntimeException('Basket should be set prior calling this func');
		}

		$orderItems = new OrderItems(basket: $this->basket);
		foreach ($orderItems->getItems() as $item) {
			$requested = $item->qty;
			if (isset($this->desiredQty[$item->item_id])) {
				$requested = intval($this->desiredQty[$item->item_id]);
			}

			if ($requested === 0) {
				continue;
			}

			if (
				!$item->vwItem->isInStock()
				|| ($item->vwItem->shallTrackInventory() && $item->vwItem->available_qty < $requested)
			) {
				$error = Yii::t('app', 'Item "{title}" is out of stock.', [
					'title' => $item->vwItem->getTitle()
				]);

				if ($item->vwItem->shallTrackInventory()) {
					$error .= ' ' . Yii::t('app', 'Available {available}, Requested: {requested}', [
						'available' => $item->vwItem->available_qty,
						'requested' => $requested
					]);
				}
				$this->addError($model, $attribute, $error);
				return;
			}
		}
	}
}
