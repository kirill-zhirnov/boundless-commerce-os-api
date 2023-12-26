<?php

namespace app\modules\orders\components;

use app\modules\inventory\models\InventoryMovement;
use app\modules\inventory\models\InventoryMovementItem;
use app\modules\inventory\models\InventoryOption;
use app\modules\orders\components\manipulatorForReserve\NotEnoughStockException;
use app\modules\orders\components\orderItems\BasketProvider;
use app\modules\orders\models\Orders;
use app\modules\orders\models\Reserve;
use app\modules\orders\models\ReserveItem;
use app\modules\user\models\Person;
use yii\db\Connection;
use yii\db\Expression;
use Yii;

class ManipulatorForReserve
{
	protected ?Orders $order;
	protected ?Reserve $reserve;
	protected InventoryMovement|null $inventoryMovement;
	protected Connection $db;

	public function __construct()
	{
		$this->db = Yii::$app->get('instanceDb');
	}

	public function createReserveByBasket(Person|null $person = null, array|null $movementProps = null): Reserve
	{
		$this->createReserve();

		$movementReason = $this->reserve->isCompleted()
			? [InventoryOption::CATEGORY_SYSTEM_CHANGE_QTY, InventoryOption::ALIAS_AVAILABLE_TO_OUTSIDE]
			: [InventoryOption::CATEGORY_SYSTEM_CHANGE_QTY, InventoryOption::ALIAS_AVAILABLE_TO_RESERVE]
		;
		$this->inventoryMovement = InventoryMovement::createByReason(
			$movementReason[0],
			$movementReason[1],
			$this->reserve,
			$person,
			$movementProps
		);

		if ($this->order->basket) {
			$this->reserveBasketItems();
			$this->order->basket->makeInactive();
		}

		$this->rmEmptyInventoryMovement();

		return $this->reserve;
	}

	public function setOrder(Orders $order): self
	{
		$this->order = $order;
		return $this;
	}

	public function setReserve(Reserve $reserve): self
	{
		$this->reserve = $reserve;
		return $this;
	}

	public function setReserveCompleted(Person|null $person = null, array|null $movementProps = null): void
	{
		if (!isset($this->reserve)) {
			throw new \RuntimeException('Reserve must be set before calling this func');
		}

		if ($this->reserve->isCompleted()) {
			throw new \RuntimeException("Reserve {$this->reserve->reserve_id} has already marked as completed.");
		}

		$this->reserve->completed_at = new Expression('now()');
		$this->reserve->save(false);

		$this->inventoryMovement = InventoryMovement::createByReason(
			InventoryOption::CATEGORY_SYSTEM_CHANGE_QTY,
			InventoryOption::ALIAS_RESERVED_TO_OUTSIDE,
			$this->reserve,
			$person,
			$movementProps
		);
		$this->moveItemsToOutside();
		$this->inventoryMovement->destroyIfEmpty();
	}

	public function setReserveUncompleted(Person|null $person = null, array|null $movementProps = null): void
	{
		if (!isset($this->reserve)) {
			throw new \RuntimeException('Reserve must be set before calling this func');
		}

		if (!$this->reserve->isCompleted()) {
			throw new \RuntimeException("Reserve {$this->reserve->reserve_id} has already marked as uncompleted.");
		}

		$this->reserve->completed_at = null;
		$this->reserve->save(false);

		$this->inventoryMovement = InventoryMovement::createByReason(
			InventoryOption::CATEGORY_SYSTEM_CHANGE_QTY,
			InventoryOption::ALIAS_OUTSIDE_TO_RESERVED,
			$this->reserve,
			$person,
			$movementProps
		);
		$this->moveItemsToReservedFromOutside();
		$this->inventoryMovement->destroyIfEmpty();
	}

	protected function moveItemsToOutside():void
	{
		$items = $this->reserve->findReservedItems();
		foreach ($items as $item) {
			if ($item->isCompleted()) {
				throw new \RuntimeException('ReserveItem #' . $item->reserve_item_id . ' has already marked as completed');
			}

			$item->completed_at = new Expression('now()');
			$item->save(false);

			if ($item->vwItem->shallTrackInventory()) {
				$item->stock?->changeQty(null, $item->qty * -1);

				$movementItem = new InventoryMovementItem();
				$movementItem->attributes = [
					'movement_id' => $this->inventoryMovement->movement_id,
					'item_id' => $item->item_id,
					'from_location_id' => $item->stock?->location_id,
					'to_location_id' => null,
					'available_qty_diff' => 0,
					'reserved_qty_diff' => $item->qty * -1
				];
				$movementItem->save(false);
			}
		}
	}

	protected function moveItemsToReservedFromOutside(): void
	{
		$items = $this->reserve->findReservedItems();
		foreach ($items as $item) {
			if (!$item->isCompleted()) {
				throw new \RuntimeException('ReserveItem #' . $item->reserve_item_id . ' has already marked as incompleted');
			}

			$item->completed_at = null;
			$item->save(false);

			if ($item->vwItem->shallTrackInventory()) {
				$item->stock?->changeQty(null, $item->qty);

				$movementItem = new InventoryMovementItem();
				$movementItem->attributes = [
					'movement_id' => $this->inventoryMovement->movement_id,
					'item_id' => $item->item_id,
					'from_location_id' => null,
					'to_location_id' => $item->stock?->location_id,
					'available_qty_diff' => 0,
					'reserved_qty_diff' => $item->qty
				];
				$movementItem->save(false);
			}
		}
	}

	protected function reserveBasketItems(): void
	{
		$reserveCompleted = $this->reserve->isCompleted();
		$basketProvider = new BasketProvider($this->order->basket);
		foreach ($basketProvider->getItems() as $item) {
			if ($item->vwItem->track_inventory) {
				$this->reserveStockItem($item->item_id, $item->qty, $item->item_price_id, null, $reserveCompleted);
			} else {
				$this->createReserveItem(null, null, $item->item_id, $item->qty, $item->item_price_id, $reserveCompleted);
			}
		}
	}

	protected function reserveStockItem(int $itemId, int $qty, int $itemPriceId, int|null $locationId = null, bool $reserveCompleted = false)
	{
		$locationSql = '';
		$params = ['item' => $itemId];

		if ($locationId) {
			$params['location'] = $locationId;
			$locationSql = 'and s.location_id = :location';
		}

		$needToReserve = $qty;
		$rows = $this->db->createCommand("
			select
				s.stock_id,
				s.location_id,
				s.available_qty,
				s.item_id
			from
				inventory_stock s
				inner join inventory_location l using(location_id)
				inner join warehouse w using(warehouse_id)
			where
				s.item_id = :item
				and s.available_qty > 0
				" . $locationSql . "
			order by w.sort
		")
			->bindValues($params)
			->queryAll()
		;

		foreach ($rows as $row) {
			$writeOffQty = min($needToReserve, $row['available_qty']);
			$this->createReserveItem($row['stock_id'], $row['location_id'], $row['item_id'], $writeOffQty, $itemPriceId, $reserveCompleted);

			$needToReserve -= $writeOffQty;
			if ($needToReserve === 0) {
				break;
			}
		}

		if ($needToReserve > 0) {
			$exception = new NotEnoughStockException();
			$exception->setItem($itemId, $qty);

			throw $exception;
		}
	}

	protected function createReserveItem(int|null $stockId, int|null $locationId, int $itemId, int $qty, int $itemPriceId, bool $isCompleted = false)
	{
		$reservedQty = $isCompleted ? 0 : $qty;

		$query = ReserveItem::find()
			->where([
				'reserve_id' => $this->reserve->reserve_id,
				'item_id' => $itemId
			])
		;

		if ($stockId) {
			$query->andWhere(['stock_id' => $stockId]);
		}

		/** @var ReserveItem|null $reserveItem */
		$reserveItem = $query->one();
		if ($reserveItem) {
			$reserveItem->attributes = [
				'qty' => $reserveItem->qty + $qty,
				'completed_at' => $isCompleted ? new Expression('now()') : null
			];
			if (!$reserveItem->save(false)) {
				throw new \RuntimeException('Cannot save reserveItem:' . print_r($reserveItem->getErrors(), 1));
			}
		} else {
			$reserveItem = new ReserveItem();
			$reserveItem->attributes = [
				'reserve_id' => $this->reserve->reserve_id,
				'stock_id' => $stockId,
				'item_id' => $itemId,
				'qty' => $qty,
				'item_price_id' => $itemPriceId,
				'completed_at' => $isCompleted ? new Expression('now()') : null
			];
			if (!$reserveItem->save(false)) {
				throw new \RuntimeException('Cannot save reserveItem2:' . print_r($reserveItem->getErrors(), 1));
			}
		}

		if ($stockId !== null) {
			try {
				$this->db->createCommand("
					update
						inventory_stock
					set
						available_qty = available_qty - :qty,
						reserved_qty = reserved_qty + :reservedQty
					where
						stock_id = :stockId
				")
					->bindValues([
						'qty' => $qty,
						'reservedQty' => $reservedQty,
						'stockId' => $stockId
					])
					->execute()
				;
			} catch (\Exception $e) {
				$stockException = new NotEnoughStockException();
				$stockException->setItem($itemId, $qty);

				throw $stockException;
			}

			if (!$locationId) {
				throw new \RuntimeException('If stockId specified, locationId cannot be empty!');
			}
//			this.itemsChangedQty.push(itemId);

			$movementItem = new InventoryMovementItem();
			$movementItem->attributes = [
				'movement_id' => $this->inventoryMovement->movement_id,
				'item_id' => $itemId,
				'from_location_id' => $locationId,
				'to_location_id' => $locationId,
				'available_qty_diff' => $qty * -1,
				'reserved_qty_diff' => $reservedQty === 0 ? null : $reservedQty
			];
			$movementItem->save(false);
		}
	}

	protected function createReserve(): void
	{
		if (!isset($this->order)) {
			throw new \RuntimeException('Order must be provided before calling createReserve');
		}

		$this->reserve = Reserve::createByOrderId($this->order->order_id);
		$this->reserve->completed_at = $this->order->status->isStockOutside() ? new Expression('now()') : null;
		$this->reserve->save(false);
	}

	protected function rmEmptyInventoryMovement(): void
	{
		$totalItems = $this->inventoryMovement->calcItems();
		if ($totalItems === 0) {
			$this->inventoryMovement->delete();
			$this->inventoryMovement = null;
		}
	}
}
