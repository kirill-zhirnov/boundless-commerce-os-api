<?php

namespace app\modules\orders\formModels\checkout;

use app\modules\inventory\models\VwInventoryItem;
use app\modules\orders\components\manipulatorForReserve\NotEnoughStockException;
use app\modules\orders\components\OrderCreator;
use app\modules\orders\models\Orders;
use yii\base\Model;
use Yii;

class OrderPlacerForm extends Model
{
	public $order_id;

	protected ?Orders $order;

	public function rules(): array
	{
		return [];
	}

	public function save(): bool
	{
		if (!isset($this->order)) {
			throw new \RuntimeException('Order must be set pripor calling this method');
		}

		$orderCreator = new OrderCreator(
			$this->order,
			function(NotEnoughStockException $e, VwInventoryItem $item) {
				$error = Yii::t('app', 'Not enough stock for "{title}", requested: {requested}, available: {available}', [
					'title' => $item->getTitle(),
					'requested' => $e->requestedQty,
					'available' => $item->available_qty
				]);
				$this->addError('order_id', $error);
			}
		);
		return $orderCreator->createFromDraft();
	}

	public function getOrder(): ?Orders
	{
		return $this->order;
	}

	public function setOrder(?Orders $order): self
	{
		$this->order = $order;
		return $this;
	}
}
