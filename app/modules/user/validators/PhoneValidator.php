<?php

namespace app\modules\user\validators;

use app\modules\system\models\Setting;
use yii\validators\Validator;
use Yii;

class PhoneValidator extends Validator
{
	public $mask;
	public $placeholder;

	public function init()
	{
		parent::init();

		if ($this->message === null) {
			//for Yii i18n parser:
			Yii::t('app', 'The phone number has an incorrect format. An example of valid number: {placeholder}.');

			$this->message = 'The phone number has an incorrect format. An example of valid number: {placeholder}';
		}

		$settings = Setting::getLocaleSettings();

		if ($this->mask === null) {
			$this->mask = $settings['phone']['mask'];
		}

		if ($this->placeholder === null) {
			$this->placeholder = $settings['phone']['placeholder'];
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
		$value = trim(strval($value));
		if ($value === '' || is_null($this->mask) || $this->mask === '') {
			return null;
		}

		try {
			if (!preg_match($this->makeRegExpPatternByMask($this->mask), $value)) {
				return [
					Yii::t('app', $this->message, ['placeholder' => $this->placeholder]),
					[]
				];
			}
		} catch (\Exception $e) {
		}

		return null;
	}

	public function makeRegExpPatternByMask(string $mask): string
	{
		$pattern = str_replace(['P', '0', 'X', '(', ')'], ['\+', '\d', '.?', '\(', '\)'], $mask);
		return '/^' . $pattern . '$/i';
	}
}
