<?php

namespace app\modules\user\formModels;

use app\modules\orders\models\Orders;
use app\validators\AliasValidator;
use Yii;
use yii\base\Model;
use app\modules\user\models\CustomerGroup;
use yii\db\Query;

class CustomerGroupForm extends Model
{
	public $title;
	public $alias;

	protected ?CustomerGroup $customerGroup;

	public function rules(): array
	{
		return [
			[['title'], 'required'],
			[['title', 'alias'], 'string', 'max' => 255],
			['alias', AliasValidator::class],
			[
				['alias'],
				'unique',
				'targetClass' => CustomerGroup::class,
				'targetAttribute' => 'alias',
				'filter' => function(Query $query) {
					if (isset($this->customerGroup) && !$this->customerGroup->isNewRecord) {
						$query->andWhere('group_id != :groupId', [
							'groupId' => $this->customerGroup->group_id
						]);
					}
				}
			],
		];
	}

	public function save(): bool
	{
		if (!$this->validate()) {
			return false;
		}

		$isUpdated = true;
		if (!isset($this->customerGroup)) {
			$this->customerGroup = new CustomerGroup();
			$isUpdated = false;
		}

		$this->customerGroup->title = $this->title;
		$this->customerGroup->alias = trim($this->alias) == '' ? null : trim($this->alias);

		$this->customerGroup->save(false);

		/** @var \app\components\InstancesQueue $queue */
		$queue = Yii::$app->queue;
		if ($isUpdated) {
			$queue->modelUpdated(CustomerGroup::class, [$this->customerGroup->group_id]);
		} else {
			$queue->modelCreated(CustomerGroup::class, [$this->customerGroup->group_id]);
		}

		return true;
	}


	public function getCustomerGroup(): ?CustomerGroup
	{
		return $this->customerGroup;
	}

	public function setCustomerGroup(?CustomerGroup $customerGroup): self
	{
		$this->customerGroup = $customerGroup;
		return $this;
	}
}
