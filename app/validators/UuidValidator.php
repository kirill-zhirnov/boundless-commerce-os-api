<?php

namespace app\validators;

use yii\validators\Validator;
use Ramsey\Uuid\Validator\GenericValidator;
use Yii;

class UuidValidator extends Validator
{
	public function init()
	{
		parent::init();

		if ($this->message === null) {
			$this->message = Yii::t('yii', 'Incorrect Uuid value.');
		}
	}

	public function validateAttribute($model, $attribute)
	{
		$value = strval($model->$attribute);
		if ($value === '') {
			return;
		}

		$validator = new GenericValidator();
		if (!$validator->validate($value)) {
			$this->addError($model, $attribute, $this->message);
		}
	}

	protected function validateValue($value)
	{
		$valid = true;
		$value = strval($value);
		if ($value !== '') {
			$validator = new GenericValidator();
			if (!$validator->validate($value)) {
				$valid = false;
			}
		}


		return $valid ? null : [$this->message, []];
	}
}
