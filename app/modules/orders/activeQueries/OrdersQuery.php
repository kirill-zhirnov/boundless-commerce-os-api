<?php

namespace app\modules\orders\activeQueries;

use yii\db\ActiveQuery;
use yii\web\HttpException;

class OrdersQuery extends ActiveQuery
{
	public function publicOrderScope(): self
	{
		$this
			->with(['customer.personProfile', 'customer.personAddresses.vwCountry'])
			->with('orderDiscounts')
			->with([
				'orderServices.orderServiceDelivery',
				'orderServices.itemPrice',
				'orderServices.orderServiceDelivery.delivery.vwShipping',
				'orderServices.orderServiceDelivery.delivery.deliveryText',
			])
		;

		return $this;
	}

	public function orderPageScope(): self
	{
		$this
			->with(['customer.personProfile', 'customer.personAddresses.vwCountry'])
			->with(['orderDiscounts', 'orderProp'])
			->with(['paymentMethod.paymentMethodText', 'paymentMethod.paymentGateway'])
			->with(['status.statusText'])
			->with([
				'orderServices.orderServiceDelivery',
				'orderServices.itemPrice',
				'orderServices.orderServiceDelivery.delivery.vwShipping',
				'orderServices.orderServiceDelivery.delivery.deliveryText',
			])
		;

		return $this;
	}

	public function byPublicId(string $publicId)
	{
		$validator = new \app\validators\UuidValidator();
		if (!$validator->validate($publicId)) {
			throw new HttpException(422, 'ID is not valid UUID.');
		}

		$this->andWhere(['orders.public_id' => $publicId]);

		return $this;
	}
}
