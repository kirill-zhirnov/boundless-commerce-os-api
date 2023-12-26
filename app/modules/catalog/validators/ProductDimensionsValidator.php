<?php

namespace app\modules\catalog\validators;

use yii\validators\Validator;
use Yii;

class ProductDimensionsValidator extends Validator
{
	public function validateAttribute($model, $attribute)
	{
		$value = $model->$attribute;
		if (is_null($value)) {
			return;
		}

		if (!is_array($value)) {
			$this->addError($model, $attribute, Yii::t('app', 'Dimension should be an object and may contain keys "width", "height", "length", "weight".'));
			return;
		}

		$filteredObject = [];
		$validKeys = ['width', 'height', 'length', 'weight'];
		foreach ($validKeys as $key) {
			if (array_key_exists($key, $value)) {
				if (!is_null($value[$key]) && !is_numeric($value[$key])) {
					$this->addError($model, $attribute, Yii::t('app', 'Key "{key}" has wrong value: the value should null or numeric.', [
						'key' => $key
					]));
					return;
				}

				if (!is_null($value[$key])) {
					$filteredObject[$key] = floatval($value[$key]);
				}
			}
		}

		$model->$attribute = empty($filteredObject) ? null : $filteredObject;
	}
}
