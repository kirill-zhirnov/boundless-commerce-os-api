<?php

namespace app\modules\orders\components;

use app\modules\inventory\models\InventoryItem;
use app\modules\orders\components\orderItems\BasketProvider;
use app\modules\orders\components\orderItems\ProviderInterface;
use app\modules\orders\components\orderItems\ReserveProvider;
use app\modules\orders\models\Basket;
use app\modules\orders\models\BasketItem;
use app\modules\orders\models\ItemPrice;
use app\modules\orders\models\Orders;
use app\modules\orders\models\ReserveItem;

class OrderItems
{
	protected Basket $basket;
	protected Orders $order;
	protected ProviderInterface $provider;

	public function __construct(Orders $order = null, Basket $basket = null)
	{
		if ($order) {
			$this->setOrder($order);
		}

		if ($basket) {
			$this->setBasket($basket);
		}

		if (!$order && !$basket) {
			throw new \RuntimeException('Order or Basket must be specified.');
		}
	}

	public function addItem(InventoryItem $item, int $qty, ItemPrice $price)
	{
		$this->getProvider()->addItem($item, $qty, $price);
	}

	/**
	 * @param array|null $filter
	 * @return BasketItem[]|ReserveItem[]
	 */
	public function getItems(array $filter = null): array
	{
		return $this->getProvider()->getItems($filter);
	}

	public function getTotalCalculator(): TotalCalculator
	{
		return $this->getProvider()->getTotalCalculator();
	}

	public function calcTotal(): array
	{
		return $this->getProvider()
			->getTotalCalculator()
			->calcTotal()
		;
	}

	public function bulkSetQty(array $items)
	{
		$this->getProvider()->bulkSetQty($items);
	}

	public function rmItems(array $itemIds)
	{
		$this->getProvider()->rmItems($itemIds);
	}

	public function getProvider(): ProviderInterface
	{
		if (!isset($this->provider)) {
			if (isset($this->order) && $this->order?->reserve) {
				$this->provider = new ReserveProvider($this->order);
			} else {
				$this->provider = new BasketProvider($this->basket);
				if (isset($this->order)) {
					$this->provider->setOrder($this->order);
				}
			}
		}

		return $this->provider;
	}

	protected function setBasket(Basket $basket): OrderItems
	{
		$this->basket = $basket;
		return $this;
	}

	protected function setOrder(Orders $order): OrderItems
	{
		$this->order = $order;
		if ($order->basket) {
			$this->setBasket($order->basket);
		}

		return $this;
	}
}
