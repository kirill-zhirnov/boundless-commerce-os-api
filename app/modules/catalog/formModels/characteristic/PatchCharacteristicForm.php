<?php

namespace app\modules\catalog\formModels\characteristic;

class PatchCharacteristicForm extends CharacteristicForm
{
	public function rules(): array
	{
		return $this->getBasicRules();
	}

	public function save(): bool
	{
		if (!isset($this->characteristic)) {
			throw new \RuntimeException('For Patch Model a characteristic should be set prior calling this func');
		}

		if (!$this->validate()) {
			return false;
		}

		$this->saveCharacteristicFields();
		$this->saveTextFields();
		$this->saveCases();

		return true;
	}

	protected function saveTextFields()
	{
		$shallSave = false;
		$textRow = $this->characteristic->characteristicTextDefault;

		foreach (['title', 'help'] as $field) {
			if (!is_null($this->{$field})) {
				$shallSave = true;

				switch ($field) {
					case 'title':
						$textRow->title = trim($this->title);
						break;

					case 'help':
						$textRow->help = trim($this->help);
						break;
				}
			}
		}

		if (!empty($shallSave)) {
			$textRow->save(false);
		}
	}

	protected function saveCharacteristicFields()
	{
		$shallSave = false;
		foreach (['sort', 'alias'] as $field) {
			if (!is_null($this->{$field})) {
				$shallSave = true;

				switch ($field) {
					case 'sort':
						$this->characteristic->sort = $this->sort;
						break;
					case 'alias':
						$this->characteristic->alias = $this->alias;
						break;
				}
			}
		}

		if (!empty($shallSave)) {
			$this->characteristic->save(false);
		}
	}
}
