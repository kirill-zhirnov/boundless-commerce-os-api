<?php

namespace app\modules\orders\components\orderItems;

use app\modules\catalog\models\ProductProp;
use app\modules\catalog\models\TaxClass;
use app\modules\orders\components\TotalCalculator;
use app\modules\orders\models\BasketItem;
use app\modules\orders\models\OrderDiscount;
use app\modules\orders\models\Orders;
use app\modules\orders\models\ReserveItem;
use app\modules\system\models\Setting;
use app\modules\user\models\PersonAddress;
use yii\db\ActiveQuery;
use yii\db\Connection;
use Yii;
use app\modules\inventory\models\VwInventoryItem;

abstract class BasicProvider
{
	protected Orders $order;

	abstract public function getTotalCalculator(): TotalCalculator;

	abstract public function getItems(array $filter = null): array;

	public function reCalcOrderTotal()
	{
		if (!isset($this->order)) {
			return;
		}

		$this->order->updateTotalsByTotalCalculator($this->getTotalCalculator());
	}

	protected function populateTotalCalculator(TotalCalculator $calculator): void
	{
		if (isset($this->order)) {
			$orderRow = $this->getDb()->createCommand("
			select
				payment_method.mark_up,
				coalesce(not_delivery_service.qty, 0) as services_qty,
				coalesce(not_delivery_service.price, 0) as services_price,
				coalesce(shipping_service.qty, 0) as shipping_qty,
				coalesce(shipping_service.price, 0) as shipping_price
			from
				orders
				left join payment_method using(payment_method_id)
				left join (
					select
						order_id,
						coalesce(sum(order_service.qty), 0) as qty,
						coalesce(sum(order_service.total_price), 0) as price
					from
						order_service
					where
						order_id = :orderId
						and is_delivery is false
					group by order_id
				) as not_delivery_service using(order_id)
			left join (
				select
					order_id,
					coalesce(sum(order_service.qty), 0) as qty,
					coalesce(sum(order_service.total_price), 0) as price
				from
					order_service
				where
					order_id = :orderId
					and is_delivery is true
				group by order_id
			) as shipping_service using(order_id)
			where
				order_id = :orderId
		")
				->bindValues(['orderId' => $this->order->order_id])
				->queryOne()
			;

			$calculator
				->setTaxSettings(Setting::getSystemTax())
				->setShipping($orderRow['shipping_price'], $orderRow['shipping_qty'])
				->setServicesTotal($orderRow['services_price'], intval($orderRow['services_qty']))
				->setPaymentMarkUp($orderRow['mark_up'])
			;

			/** @var OrderDiscount[] $discounts */
			$discounts = OrderDiscount::find()
				->where(['order_id' => $this->order->order_id])
				->orderBy(['discount_id' => SORT_ASC])
				->all()
			;

			foreach ($discounts as $discount) {
				$calculator->addDiscount($discount->discount_type, $discount->value);
			}

			if ($this->order->customer_id) {
				/** @var PersonAddress[] $addresses */
				$addresses = PersonAddress::find()
					->where([
						'person_id' => $this->order->customer_id,
						'type' => [PersonAddress::TYPE_SHIPPING, PersonAddress::TYPE_BILLING]
					])
					->all()
				;

				foreach ($addresses as $address) {
					if ($address->isShippingType()) {
						$calculator->setShippingLocation($address);
					} else if ($address->isBillingType()) {
						$calculator->setBillingLocation($address);
					}
				}
			}
		}

		/** @var ReserveItem|BasketItem $item */
		foreach ($this->getItems() as $item) {
			/** @var VwInventoryItem $vwItem */
			$vwItem = $item->vwItem;
			$taxStatus = ProductProp::TAX_STATUS_NONE;
			$taxClassId = null;

			if (in_array($vwItem->type, [VwInventoryItem::TYPE_VARIANT, VwInventoryItem::TYPE_PRODUCT])) {
				$taxStatus = $vwItem->product['tax_status'];
				$taxClassId = $vwItem->product['tax_class_id'];
			}

			$calculator->addItem($vwItem->item_id, $item->itemPrice->final_price, $item->qty, $taxStatus, $taxClassId);
		}

		/** @var TaxClass[] $taxClasses */
		$taxClasses = TaxClass::find()
			->with(['taxRates' => fn (ActiveQuery $query) => $query->orderBy(['tax_rate.priority' => SORT_ASC])])
			->all()
		;
		$calculator->setTaxClasses($taxClasses);
	}

	public function getDb(): Connection
	{
		return Yii::$app->get('instanceDb');
	}
}
