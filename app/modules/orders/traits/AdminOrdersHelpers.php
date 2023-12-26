<?php

namespace app\modules\orders\traits;

use app\modules\orders\models\AdminOrders;
use app\modules\orders\models\Orders;
use app\validators\UuidValidator;
use yii\web\HttpException;

trait AdminOrdersHelpers
{
	public function findAdminOrder($id, bool $withPublicScope = false): AdminOrders|null
	{
		$uuidValidator = new UuidValidator();
		$query = AdminOrders::find();

		if (is_numeric($id)) {
			$query->where(['order_id' => $id]);
		} else if ($uuidValidator->validate($id)) {
			$query->where(['public_id' => $id]);
		} else {
			throw new HttpException(422, 'ID should be either Numeric or UUID.');
		}

		if ($withPublicScope) {
			$query->orderPageScope();
		}

		$order = $query->one();
		if (!$order) {
			throw new HttpException(404, 'Order not found.');
		}

		return $order;
	}
}
