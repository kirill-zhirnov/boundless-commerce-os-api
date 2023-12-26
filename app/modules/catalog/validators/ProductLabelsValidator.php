<?php

namespace app\modules\catalog\validators;

use app\modules\catalog\models\Category;
use app\modules\catalog\models\Label;
use yii\validators\Validator;
use Yii;

class ProductLabelsValidator extends Validator
{
	public function validateAttribute($model, $attribute)
	{
		$result = $this->validateValue($model->$attribute);
		if ($result !== null) {
			$this->addError($model, $attribute, $result[0]);
			return;
		}
	}

	protected function validateValue($value): null|array
	{
		if (is_null($value)) {
			return null;
		}

		if (!is_array($value)) {
			return [Yii::t('app', 'Labels should be an array of object with "label_id" key.'), []];
		}

		foreach ($value as $key => $row) {
			if (!isset($row['label_id'])) {
				return [Yii::t('app', 'Key "label_id" is missed for row "{i}".', ['i' => $key]), []];
			}

			$labelId = intval($row['label_id']);
			$label = Label::find()
				->where([
					'label.label_id' => $labelId,
					'label.deleted_at' => null,
				])
				->one();
			;

			if (!$label) {
				return [Yii::t('app', 'Cant find a label with "label_id" = {value}.', ['value' => $labelId]), []];
			}
		}

		return null;
	}
}
