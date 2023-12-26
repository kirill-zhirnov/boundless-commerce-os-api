<?php

namespace app\modules\catalog\validators;

use app\modules\catalog\models\Category;
use yii\validators\Validator;
use Yii;

class CategoryExistsValidator extends Validator
{
	public $filter;

	public $allowMinusOneAsNull = true;

	public function init()
	{
		parent::init();

		if ($this->message === null) {
			$this->message = Yii::t('yii', 'Category doesnt exists.');
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
			$model->$attribute = intval($model->$attribute);
		}
	}

	protected function validateValue($value)
	{
		$value = intval($value);

		//for parent=null
		if ($this->allowMinusOneAsNull && $value === -1) {
			return null;
		}

		$query = Category::find()
			->where([
				'category.category_id' => $value,
				'category.deleted_at' => null,
				'category.status' => [Category::STATUS_PUBLISHED, Category::STATUS_HIDDEN]
			])
		;

		if (is_callable($this->filter)) {
			call_user_func($this->filter, $query);
		}

		$category = $query->one();
		if (!$category) {
			return [$this->message, []];
		}

		return null;
	}
}
