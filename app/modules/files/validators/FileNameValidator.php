<?php

namespace app\modules\files\validators;

use yii\validators\Validator;
use RuntimeException;
use Yii;

class FileNameValidator extends Validator
{
	public $extensions;

	public function validateAttribute($model, $attribute)
	{
		if (empty($this->extensions) || !is_array($this->extensions)) {
			throw new RuntimeException('extensions shpould be an array of allowed ext. (["jpg", ...])');
		}

		$value = trim($model->$attribute);
		if ($value == '') {
			$this->addError($model, $attribute, 'file_name should be passed');
			return;
		}

		$info = pathinfo($value);
		$fileExt = mb_strtolower($info['extension']);

		if (!in_array($fileExt, $this->extensions)) {
			$this->addError(
				$model,
				$attribute,
				Yii::t('app', 'Incorrect extension. Allowed extensions: {list}', [
					'list' => implode(', ', $this->extensions)
				])
			);
			return;
		}
	}
}
