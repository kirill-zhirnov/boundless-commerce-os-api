<?php

namespace app\modules\orders\components;
use app\modules\catalog\models\PointSale;
use app\modules\inventory\models\VwInventoryItem;
use app\modules\orders\components\manipulatorForReserve\NotEnoughStockException;
use app\modules\orders\models\Orders;
use app\modules\system\models\Setting;
use app\modules\user\models\Person;
use yii\db\Connection;
use yii\db\Expression;
use Yii;

class OrderCreator
{
	public function __construct(
		protected Orders $order,
		protected $onNotEnoughStock = null
	)
	{}

	public function createFromDraft(): bool
	{
		/** @var Connection $db */
		$db = Yii::$app->get('instanceDb');
		$transaction = $db->beginTransaction();

		try {
			$this->saveOrderProps();

			$manipulator = new ManipulatorForReserve();
			$manipulator->setOrder($this->order);
			$manipulator->createReserveByBasket($this->order->customer);

			$this->order->refresh();
			$this->order->reCalcOrderTotal();

			$transaction->commit();

			/** @var \app\components\InstancesQueue $queue */
			Yii::$app->queue
				->modelCreated(Orders::class, [$this->order->order_id], ['status_id' => $this->order->status_id], true, true)
			;

			return true;
		} catch (NotEnoughStockException $e) {
			$transaction->rollBack();
			$item = VwInventoryItem::findOne($e->itemId);

			if (isset($this->onNotEnoughStock) && is_callable($this->onNotEnoughStock)) {
				/**
				 * Yii::t(
				 * 'app',
				 * 'Not enough stock for "{title}", requested: {requested}, available: {available}',
				 * [
				 * 	'title' => $item->getTitle(),
				 * 	'requested' => $e->requestedQty,
				 * 	'available' => $item->available_qty
				 * ]);
				 */
				call_user_func($this->onNotEnoughStock, $e, $item);
			}
		} catch (\Exception $e) {
			$transaction->rollBack();

			throw $e;
		}

		return false;
	}

	public function saveOrderProps()
	{
		$statusId = Setting::getNewOrderStatusId();
		$this->order->point_id = PointSale::DEFAULT_POINT;
		$this->order->status_id = $statusId;
		$this->order->publishing_status = Orders::STATUS_PUBLISHED;
		$this->order->created_at = new Expression('now()');

		/** @var \yii\web\User $customerUser */
		$customerUser = Yii::$app->customerUser;
		if (!$customerUser->isGuest) {
			/** @var Person $loggedInCustomer */
			$loggedInCustomer = $customerUser->getIdentity()->getPerson();

			$this->order->created_by = $loggedInCustomer->person_id;
		}

		if (!$this->order->save(false)) {
			throw new \RuntimeException('Cannot save payment method:', print_r($this->order->getErrors(), 1));
		}
	}
}
