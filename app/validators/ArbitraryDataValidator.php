<?php

namespace app\validators;
use yii\validators\Validator;
use Yii;

class ArbitraryDataValidator extends Validator
{
	public function validateAttribute($model, $attribute)
	{
		$value = $model->$attribute;
		if (isset($value) && !is_array($value)) {
			$this->addError($model, $attribute, Yii::t('app', 'Arbitrary data should be a key-value object.'));
			return;
		}
	}
}
