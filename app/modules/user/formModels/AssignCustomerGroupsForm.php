<?php

namespace app\modules\user\formModels;

use app\modules\delivery\models\VwCountry;
use app\modules\user\models\CustomerGroup;
use app\modules\user\models\Person;
use app\modules\user\models\PersonGroupRel;
use yii\base\Model;
use yii\db\Query;

class AssignCustomerGroupsForm extends Model
{
	public $groups;

	protected ?Person $customer;

	public function rules(): array
	{
		return [
			['groups', 'each', 'rule' => ['integer']],
			[
				'groups',
				'each',
				'rule' => [
					'exist',
					'targetClass' => CustomerGroup::class,
					'targetAttribute' => 'group_id',
					'filter' => fn(Query $query) => $query->andWhere(['deleted_at' => null])
				]
			]
		];
	}

	public function save(): bool
	{
		if (!isset($this->customer)) {
			throw new \RuntimeException('Customer should be set prior saving.');
		}

		if (!$this->validate()) {
			return false;
		}

		if (is_array($this->groups)) {
			foreach ($this->groups as $groupId) {
				$group = CustomerGroup::findOne($groupId);

				if ($group) {
					$this->customer->assignGroup($group);
				}
			}
		}

		$deleteCondition = ['person_id' => $this->customer->person_id];
		if (!empty($this->groups)) {
			$deleteCondition = [
				'and',
				['person_id' => $this->customer->person_id],
				['not in', 'group_id', $this->groups]
			];
		}

		PersonGroupRel::deleteAll($deleteCondition);

		return true;
	}

	public function setCustomer(?Person $customer): self
	{
		$this->customer = $customer;
		return $this;
	}
}
