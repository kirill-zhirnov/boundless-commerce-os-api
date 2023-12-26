<?php

namespace app\modules\payment\activeQueries;

use app\modules\payment\models\PaymentGateway;
use app\modules\system\models\Site;
use yii\db\ActiveQuery;

class PaymentMethodQuery extends ActiveQuery
{
	public function onlyConfigured(): self
	{
		$this->innerJoinWith('paymentGateway');
		$this->andWhere('
			(
				(
					payment_gateway.alias = :paypalAlias
					and payment_method.config is not null
					and payment_method.config->>:modeKey is not null
				)
				or (payment_gateway.alias != :paypalAlias)
			)
		', [
			'modeKey' => 'mode',
			'paypalAlias' => PaymentGateway::ALIAS_PAYPAL
		]);

		return $this;
	}

	public function publicScope(): self
	{
		$this->with(['paymentMethodText', 'paymentGateway']);
		$this->orderBy(['payment_method.sort' => SORT_ASC]);

		return $this;
	}

	public function findByDelivery($deliveryId): self
	{
		$this->distinct();
		$this->joinWith('paymentMethodDeliveries.deliverySite');

		$this->where([
			'payment_method.site_id' => Site::DEFAULT_SITE,
			'payment_method.deleted_at' => null
		]);

		if ($deliveryId) {
			$this->andWhere('
				payment_method.for_all_delivery is TRUE
				OR (
					delivery_site.site_id = :deliverySiteId
					and delivery_site.delivery_id = :deliveryDeliveryId
				)
			', [
				'deliverySiteId' => Site::DEFAULT_SITE,
				'deliveryDeliveryId' => $deliveryId
			]);
		} else {
			$this->andWhere('payment_method.for_all_delivery is TRUE');
		}

		return $this;
	}
}
