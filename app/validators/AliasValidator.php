<?php

namespace app\validators;

use yii\validators\Validator;
use Yii;

class AliasValidator extends Validator
{
	public function init()
	{
		parent::init();

		if ($this->message === null) {
			$this->message = Yii::t('yii', 'Alias can contain only alphabetic, numeric symbols, and "_" or "-".');
		}
	}

	public function validateAttribute($model, $attribute)
	{
		$result = $this->validateValue($model->$attribute);
		if ($result !== null) {
			$this->addError($model, $attribute, $result[0]);
			return;
		}
	}

	protected function validateValue($value)
	{
		$value = strval($value);

		if (!preg_match('/^[a-z0-9\-_]*$/i', $value)) {
			return [$this->message, []];
		}

		return null;
	}
}
