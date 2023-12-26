<?php

namespace app\modules\catalog\formModels\characteristic;

use app\modules\catalog\models\Characteristic;
use app\modules\catalog\models\CharacteristicTypeCase;
use app\modules\catalog\models\CommodityGroup;
use yii\base\Model;
use yii\db\Query;
use Yii;

class CharacteristicForm extends Model
{
	public $title;
	public $alias;
	public $group_id;
	public $type;
	public $help;
	public $sort;
	public $cases;

	protected ?Characteristic $characteristic;

	public function rules(): array
	{
		return array_merge(
			$this->getBasicRules(),
			[
				[['title', 'alias'], 'required'],
				[['title'], 'trim'],
				['group_id', 'required'],
				[
					'group_id',
					'exist',
					'targetClass' => CommodityGroup::class,
					'targetAttribute' => 'group_id'
				],
				['type', 'required'],
				[
					'type',
					'in',
					'range' => [
						Characteristic::TYPE_CHECKBOX, Characteristic::TYPE_RADIO, Characteristic::TYPE_SELECT,
						Characteristic::TYPE_TEXT, Characteristic::TYPE_TEXTAREA, Characteristic::TYPE_WYSIWYG
					]
				],
			]
		);
	}

	public function save(): bool
	{
		if (!$this->validate()) {
			return false;
		}

		if (!isset($this->characteristic)) {
			$this->characteristic = new Characteristic();
		}

		$isNew = $this->characteristic->isNewRecord;
		$this->characteristic->attributes = [
			'group_id' => $this->group_id,
			'type' => $this->type,
			'alias' => $this->alias,
			'sort' => $this->sort,
		];
		$this->characteristic->save(false);

		$text = $this->characteristic->characteristicTextDefault;
		$text->attributes = [
			'title' => $this->title,
			'help' => $this->help
		];
		$text->save();

		$this->saveCases();

		return true;
	}

	protected function saveCases()
	{
		if (!is_array($this->cases)) {
			return;
		}

		$activeIds = [];
		foreach ($this->cases as $case) {
			$title = $case['title'];

			if (isset($case['id'])) {
				$row = CharacteristicTypeCase::findOne([
					'case_id' => intval($case['id']),
					'characteristic_id' => $this->characteristic->characteristic_id
				]);
			} else {
				$row = new CharacteristicTypeCase();
				$row->attributes = [
					'characteristic_id' => $this->characteristic->characteristic_id
				];
				$row->save(false);
				$row->refresh();
			}

			if (isset($row)) {
				$activeIds[] = $row->case_id;
				$row->textDefault->title = $title;
				$row->textDefault->save(false);
			}
		}

		$deleteCondition = ['characteristic_id' => $this->characteristic->characteristic_id];
		if (!empty($activeIds)) {
			$deleteCondition = [
				'and',
				['characteristic_id' => $this->characteristic->characteristic_id],
				['not in', 'case_id', $activeIds]
			];
		}
		CharacteristicTypeCase::deleteAll($deleteCondition);
	}

	public function setCharacteristic(?Characteristic $characteristic): self
	{
		$this->characteristic = $characteristic;
		return $this;
	}

	public function getCharacteristic(): ?Characteristic
	{
		return $this->characteristic;
	}

	public function validateCases()
	{
		if (!is_array($this->cases)) {
			$this->addError('cases', 'Should be a list of object(s) with keys: title and optional id.');
			return;
		}

		if ($this->hasErrors('type')) {
			return;
		}

		$type = $this->type;
		if (!isset($type) && isset($this->characteristic) && !$this->characteristic->isNewRecord) {
			$type = $this->characteristic->type;
		}

		if (!empty($this->cases) && !Characteristic::isMultiCaseType($type)) {
			$this->addError('cases', 'Cases could only be specified for type: checkbox, radio, select.');
			return;
		}

		foreach ($this->cases as $i => $case) {
			if (!isset($case['title'])) {
				$this->addError('cases[' . $i . ']', 'Key "title" is required.');
				return;
			}

			if (isset($case['id'])) {
				if (!isset($this->characteristic) || $this->characteristic->isNewRecord) {
					$this->addError('cases[' . $i . ']', 'Key "id" could be specified only on update.');
					return;
				}

				$row = CharacteristicTypeCase::findOne([
					'case_id' => intval($case['id']),
					'characteristic_id' => $this->characteristic->characteristic_id
				]);
				if (!$row) {
					$this->addError('cases[' . $i . ']', 'Cannot find case with the specified ID.');
					return;
				}
				$this->cases[$i]['id'] = intval($case['id']);
			}

			$this->cases[$i]['title'] = trim($case['title']);
		}
	}

	public function getBasicRules(): array
	{
		return [
			[['title', 'alias', 'help'], 'string', 'max' => 1000],
			['alias', 'match', 'pattern' => '/^[a-z0-9\-_]+$/i'],
			[
				'alias',
				'unique',
				'targetClass' => Characteristic::class,
				'targetAttribute' => 'alias',
				'filter' => function(Query $query) {
					if (isset($this->characteristic) && !$this->characteristic->isNewRecord) {
						$query->andWhere('characteristic_id != :id', ['id' => $this->characteristic->characteristic_id]);
					}
				}
			],
			['sort', 'integer'],
			['cases', 'validateCases']
		];
	}
}
