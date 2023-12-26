<?php

namespace app\modules\orders\traits;

use app\modules\delivery\models\Delivery;
use app\modules\orders\models\Orders;
use app\validators\UuidValidator;
use yii\web\HttpException;

trait CheckoutHelpers
{
	public function findCheckoutOrder($publicId, bool $withPublicScope = false, bool $validateOnDraft = true): Orders|null
	{
		$validator = new UuidValidator();
		if (!$validator->validate($publicId)) {
			throw new HttpException(422, 'ID is not valid UUID.');
		}

		$query = Orders::find()
			->where([
				'public_id' => $publicId
			])
		;

		if ($withPublicScope) {
			$query->publicOrderScope();
		}

		/** @var Orders $order */
		$order = $query->one();

		if ($validateOnDraft && !$order->isDraft()) {
			throw new HttpException(422, 'Order is not in the Draft state.');
		}

		return $order;
	}

	public function findCheckoutDelivery($id): Delivery|null
	{
		return Delivery::find()
			->publicOptions()
			->where([
				'delivery.delivery_id' => $id,
				'delivery.deleted_at' => null,
				'delivery.status' => 'published'
			])
			->one()
		;
	}
}
