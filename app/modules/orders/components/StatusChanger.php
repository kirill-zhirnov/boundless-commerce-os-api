<?php

namespace app\modules\orders\components;
use app\modules\orders\models\Orders;
use app\modules\orders\models\OrderStatus;
use app\modules\user\models\Person;
use yii\db\Connection;
use Yii;
use yii\web\User;

class StatusChanger
{
	protected Connection $db;
	protected OrderStatus|null $fromStatus;
	protected ?OrderStatus $toStatus;

	public function __construct(
		protected Orders $order
	)
	{
		$this->db = Yii::$app->get('instanceDb');
	}

	public function changeStatus(OrderStatus $status): void
	{
		$this->fromStatus = $this->order->status;
		$this->toStatus = $status;

		if ($this->fromStatus?->status_id != $this->toStatus->status_id) {
			$this->order->status_id = $this->toStatus->status_id;
			$this->order->save(false);
			$this->order->refresh();
		}

		$this->saveStockLocation();
	}

	protected function saveStockLocation(): ?bool
	{
		$fromStockLocation = $this->fromStatus?->stock_location ?? OrderStatus::STOCK_LOCATION_BASKET;
		$toStockLocation = $this->toStatus->stock_location;
		$logOrderStatusChanged = null;

		/** @var User $customerUser */
		$customerUser = Yii::$app->customerUser;
		/** @var Person $person */
		$person = $customerUser->isGuest ? null : $customerUser->getIdentity()->getPerson();

		if ($fromStockLocation == $toStockLocation) {
			if ($fromStockLocation == OrderStatus::STOCK_LOCATION_BASKET) {
				$this->makeBasketInActive();
			}

			return null;
		}

		if ($this->fromStatus) {
			$logOrderStatusChanged = [
				'orderStatusFrom' => $this->fromStatus->status_id,
				'orderStatusTo' => $this->toStatus->status_id
			];
		}

		if ($fromStockLocation == OrderStatus::STOCK_LOCATION_BASKET) {
			$reserveManipulator = new ManipulatorForReserve();
			$reserveManipulator
				->setOrder($this->order)
				->createReserveByBasket($person, $logOrderStatusChanged)
			;
		} else {
			if ($toStockLocation == OrderStatus::STOCK_LOCATION_BASKET) {
				$reserve2Basket = new Reserve2Basket($this->order);
				$reserve2Basket
					->setMovementProps($logOrderStatusChanged)
					->process()
				;
			} else {
				if (!$this->order->reserve) {
					throw new \RuntimeException('Reserve doesnt exist, but status suppose to have reserve');
				}

				$reserveManipulator = new ManipulatorForReserve();
				$reserveManipulator->setReserve($this->order->reserve);

				switch ($toStockLocation) {
					case OrderStatus::STOCK_LOCATION_INSIDE:
						$reserveManipulator->setReserveUncompleted($person, $logOrderStatusChanged);
						break;
					case OrderStatus::STOCK_LOCATION_OUTSIDE:
						$reserveManipulator->setReserveCompleted($person, $logOrderStatusChanged);
						break;
				}
			}
		}

		return true;
	}

	protected function makeBasketInActive(): void
	{
		if ($this->order->basket?->is_active) {
			$this->order->basket->makeInactive();
		}
	}
}
