<?php

namespace app\validators;

use yii\validators\Validator;
use Yii;

class UrlKeyValidator extends Validator
{
	public $messageNotNumeric;
	public function init()
	{
		parent::init();

		if ($this->message === null) {
			$this->message = Yii::t('yii', 'Incorrect value for an URL key. Url should contain at least one alphabetic symbol, may contain numeric symbols and "_" or "-".');
		}

		if ($this->messageNotNumeric === null) {
			$this->messageNotNumeric = Yii::t('yii', 'Url should contain not numeric symbol');
		}
	}

	public function validateAttribute($model, $attribute)
	{
		$result = $this->validateValue($model->$attribute);
		if ($result !== null) {
			$this->addError($model, $attribute, $result[0]);
			return;
		}

		if (!is_null($model->$attribute)) {
			$model->$attribute = mb_strtolower(strval($model->$attribute));
		}
	}

	protected function validateValue($value)
	{
		$value = strval($value);

		if (!preg_match('/\D/', $value)) {
			return [$this->messageNotNumeric, []];
		}

		if (!preg_match('/^[a-z0-9\-_]+$/i', $value)) {
			return [$this->message, []];
		}

		return null;
	}
}
