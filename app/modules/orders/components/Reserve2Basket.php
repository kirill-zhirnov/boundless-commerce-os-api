<?php

namespace app\modules\orders\components;

use app\modules\inventory\models\InventoryMovement;
use app\modules\inventory\models\InventoryMovementItem;
use app\modules\inventory\models\InventoryOption;
use app\modules\orders\models\Basket;
use app\modules\orders\models\Orders;
use app\modules\orders\models\Reserve;
use app\modules\orders\models\ReserveItem;
use yii\db\Connection;
use Yii;
use yii\web\User;

class Reserve2Basket
{
	protected Connection $db;
	protected ?Reserve $reserve;
	protected ?Basket $basket;
	protected ?InventoryMovement $movement;
	protected array|null $movementProps = null;

	public function __construct(
		protected Orders $order
	)
	{
		$this->db = Yii::$app->get('instanceDb');
	}

	public function process(): bool
	{
		if (!$this->findReserve()) {
			return false;
		}

		$this->makeBasket();
		$this->makeMovement();

		$this->copyItemsToBasket();
		$this->moveItemsToStock();

		$this->movement->destroyIfEmpty();

		$this->order->basket_id = $this->basket->basket_id;
		$this->order->save(false);

		return true;
	}

	public function setMovementProps(array|null $movementProps): self
	{
		$this->movementProps = $movementProps;
		return $this;
	}

	protected function findReserve(): ?Reserve
	{
		$this->reserve = Reserve::find()
			->where(['order_id' => $this->order->order_id])
			->one()
		;

		return $this->reserve;
	}

	protected function makeBasket(): void
	{
		$this->basket = new Basket();
		$this->basket->attributes = ['person_id' => null, 'is_active' => false];
		$this->basket->save(false);
	}

	protected function makeMovement(): void
	{
		/** @var User $customerUser */
		$customerUser = Yii::$app->customerUser;
		$person = $customerUser->isGuest ? null : $customerUser->getIdentity()->getPerson();

		$reasonAlias = 'rmFromReserve';
		if ($this->reserve->completed_at) {
			$reasonAlias = 'outsideToAvailable';
		}

		$this->movement = InventoryMovement::createByReason(
			InventoryOption::CATEGORY_SYSTEM_CHANGE_QTY,
			$reasonAlias,
			$this->reserve,
			$person,
			$this->movementProps
		);
	}

	protected function copyItemsToBasket(): void
	{
		/** @var ReserveItem[] $reservedRows */
		$reservedRows = ReserveItem::find()
			->where(['reserve_id' => $this->reserve->reserve_id])
			->orderBy(['reserve_item_id' => SORT_ASC])
			->all()
		;

		foreach ($reservedRows as $row) {
			$this->db->createCommand("
				insert into basket_item
					(basket_id, item_id, qty, item_price_id)
				values
					(:basket, :itemId, :qty, :itemPriceId)
				on conflict (basket_id, item_id) do update
				set
					qty = basket_item.qty + :qty
			")
				->bindValues([
					'basket' => $this->basket->basket_id,
					'itemId' => $row->item_id,
					'qty' => $row->qty,
					'itemPriceId' => $row->item_price_id,
				])
				->execute()
			;
		}
	}

	protected function moveItemsToStock(): void
	{
//		$qtyChanged = false;
		$reservedItems = $this->reserve->findReservedItems();
		foreach ($reservedItems as $row) {
			if (!$row->vwItem->isCustomItem() && $row->vwItem->shallTrackInventory() && $row->stock_id) {
				$this->runItemMovement($row);
//				$qtyChanged = true;
			}
		}

		InventoryMovement::updateAll(['order_id' => $this->order->order_id], [
			'reserve_id' => $this->reserve->reserve_id
		]);
		$this->reserve->delete();
	}

	protected function runItemMovement(ReserveItem $reservedItem): void
	{
		$reservedQty = $reservedItem->qty;
		if ($this->reserve->completed_at) {
			$reservedQty = 0;
		}

		$this->db->createCommand("
			update
				inventory_stock
			set
				available_qty = available_qty + :qty,
				reserved_qty = reserved_qty - :reservedQty
			where
				stock_id = :stock
		")
			->bindValues([
				'qty' => $reservedItem->qty,
				'reservedQty' => $reservedQty,
				'stock' => $reservedItem->stock_id
			])
			->execute()
		;

		$movementItem = new InventoryMovementItem();
		$movementItem->attributes = [
			'movement_id' => $this->movement->movement_id,
			'item_id' => $reservedItem->item_id,
			'from_location_id' => ($this->reserve->completed_at) ? null : $reservedItem->stock->location_id,
			'to_location_id' => $reservedItem->stock->location_id,
			'available_qty_diff' => $reservedItem->qty,
			'reserved_qty_diff' => ($reservedQty) ? $reservedItem->qty * -1 : null
		];
		$movementItem->save(false);
	}
}
