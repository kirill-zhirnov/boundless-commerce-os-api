<?php

namespace app\modules\orders\formModels\orders;

use app\modules\inventory\models\VwInventoryItem;
use app\modules\orders\components\manipulatorForReserve\NotEnoughStockException;
use app\modules\orders\models\Orders;
use app\modules\orders\models\OrderStatus;
use app\modules\system\models\AdminComment;
use app\modules\system\models\Essence;
use app\modules\user\models\Person;
use app\validators\UuidValidator;
use yii\db\Connection;
use Yii;
use app\modules\orders\components\StatusChanger;
use yii\db\Expression;
use yii\web\User;

class AdminOrderAttrsForm extends OrderAttrsForm
{
	public $is_paid;
	public $status_id;
	public $internal_comment;

	protected ?int $statusChangedTo;

	public function rules(): array
	{
		return array_merge(parent::rules(), [
			[['is_paid'], 'boolean', 'on' => 'adminForm'],

			[['status_id'], 'integer', 'min' => 0, 'on' => 'adminForm'],
			[
				'status_id',
				'exist',
				'targetClass' => OrderStatus::class,
				'targetAttribute' => ['status_id' => 'status_id'],
				'on' => 'adminForm'
			],

			[['customer_id'], UuidValidator::class, 'on' => 'adminForm'],
			[
				'customer_id',
				'exist',
				'targetClass' => Person::class,
				'targetAttribute' => ['customer_id' => 'public_id'],
				'on' => 'adminForm'
			],
			['internal_comment', 'string', 'max' => 1000, 'on' => 'adminForm'],
		]);
	}

	public function save(): bool
	{
		if (!isset($this->order)) {
			throw new \RuntimeException('Order should be set prior calling this func');
		}

		if (!$this->validate()) {
			return false;
		}

		/** @var Connection $db */
		$db = Yii::$app->get('instanceDb');
		$transaction = $db->beginTransaction();

		try {
			$this->saveStatus();
			$this->saveCustomer();
			$this->savePaidStatus();
			$this->saveInternalComment();
			$this->savePaymentMethod();
			$this->saveAddresses();
			$this->saveDelivery();
			$this->saveCustomAttrs();

			$this->order->reCalcOrderTotal();

			$transaction->commit();
		} catch (NotEnoughStockException $e) {
			$transaction->rollBack();

			$item = VwInventoryItem::findOne($e->itemId);
			$error = Yii::t('app', 'Not enough stock for "{title}", requested: {requested}, available: {available}', [
				'title' => $item->getTitle(),
				'requested' => $e->requestedQty,
				'available' => $item->available_qty
			]);
			$this->addError('order_id', $error);

			return false;
		} catch (\Exception $e) {
			$transaction->rollBack();

			throw $e;
		}

		/** @var \app\components\InstancesQueue $queue */
		$queue = Yii::$app->queue;
		$diff = null;
		$notifyClient = false;
		if (isset($this->statusChangedTo)) {
			$diff = ['status_id' => $this->statusChangedTo];
			$notifyClient = true;
		}
		$queue->modelUpdated(Orders::class, [$this->order->order_id], $diff, false, $notifyClient);

		return true;
	}

	protected function saveStatus()
	{
		if (isset($this->status_id)) {
			if ($this->order->status_id != $this->status_id) {
				$statusChanger = new StatusChanger($this->order);

				$status = OrderStatus::findOne($this->status_id);
				$statusChanger->changeStatus($status);

				$this->statusChangedTo = $status->status_id;
			}
		}
	}

	protected function savePaidStatus()
	{
		if (isset($this->is_paid)) {
			if ($this->is_paid) {
				if (!$this->order->paid_at) {
					$this->order->paid_at = new Expression('now()');
					$this->order->save(false);
				}
			} else {
				if ($this->order->paid_at) {
					$this->order->paid_at = null;
					$this->order->save(false);
				}
			}
		}
	}

	protected function saveInternalComment()
	{
		if (isset($this->internal_comment) && trim($this->internal_comment) != '') {
			$essence = Essence::findOrCreateEssence($this->order->order_id, Essence::TYPE_ORDERS);

			$comment = new AdminComment();
			$comment->essence_id = $essence->essence_id;
			$comment->comment = $this->internal_comment;

			/** @var User $customerUser */
			$customerUser = Yii::$app->customerUser;
			/** @var Person $person */
			$person = $customerUser->isGuest ? null : $customerUser->getIdentity()->getPerson();

			if ($person) {
				$comment->person_id = $person->person_id;
			}

			$comment->save(false);
		}
	}

	protected function saveCustomer()
	{
		if (isset($this->customer_id)) {
			/** @var Person $person */
			$person = Person::find()->where(['public_id' => $this->customer_id])->one();
			$this->order->customer_id = $person?->person_id;
			$this->order->save(false);
		}
	}

	protected function saveContact()
	{
		$result = parent::saveContact();

		if ($result) {
			$this->order->save(false);
		}
	}

	protected function savePaymentMethod()
	{
		if (isset($this->payment_method_id)) {
			$this->order->payment_method_id = $this->payment_method_id;
			$this->order->save(false);
		}
	}
}
