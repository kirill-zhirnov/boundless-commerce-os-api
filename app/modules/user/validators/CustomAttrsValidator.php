<?php

namespace app\modules\user\validators;

use yii\validators\Validator;

class CustomAttrsValidator extends Validator
{
	public function validateAttribute($model, $attribute)
	{
		$value = $model->$attribute;
		if (!isset($value) || $value === '') {
			return;
		}

		if (!is_array($value)) {
			$this->addError($model, $attribute, \Yii::t('app', 'Attributes should be an object.'));
			return;
		}

		foreach ($value as $key => $val) {
			if (!preg_match('/^[a-z0-9_-]+$/i', $key)) {
				$this->addError($model, $attribute, \Yii::t('app', 'A root key can contain only [a-z0-9_-]+. Incorrect key: "' . $key . '".'));
				return;
			}
		}
	}
}
