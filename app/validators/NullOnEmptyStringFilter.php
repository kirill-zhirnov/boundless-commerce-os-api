<?php

namespace app\validators;
use yii\validators\Validator;

class NullOnEmptyStringFilter extends Validator
{
	public function validateAttribute($model, $attribute)
	{
		if (is_string($model->$attribute)) {
			$model->$attribute = trim($model->$attribute);
		}

		if ($model->$attribute === '') {
			$model->$attribute = null;
		}
	}
}
