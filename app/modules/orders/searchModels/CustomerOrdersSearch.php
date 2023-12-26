<?php

namespace app\modules\orders\searchModels;

use app\modules\orders\models\Orders;
use app\modules\user\models\Person;
use yii\db\ActiveQueryInterface;

class CustomerOrdersSearch extends OrdersSearch
{
	protected ?Person $customer;
	public function rules(): array
	{
		return [
			['created_at', 'validateCreatedAt'],
			['status_id', 'integer', 'min' => 0],
			['total_price', 'validateTotalPrice'],
		];
	}

//	protected function makeQuery(): ActiveQueryInterface
//	{
//		return Orders::find();
//	}

	protected function setupQueryBasics(ActiveQueryInterface $query)
	{
		if (!isset($this->customer)) {
			throw new \RuntimeException('Customer must be specified');
		}

		parent::setupQueryBasics($query);

		$query->andWhere(['person.person_id' => $this->customer->person_id]);
	}

	public function setCustomer(?Person $customer): CustomerOrdersSearch
	{
		$this->customer = $customer;
		return $this;
	}
}
