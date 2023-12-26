<?php

namespace app\modules\catalog\validators;
use app\modules\catalog\models\Category;
use yii\validators\Validator;
use Yii;

class ProductCategoriesValidator extends Validator
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
			return [Yii::t('app', 'Categories should be an array of object with "category_id" key and optional "is_default".'), []];
		}

		foreach ($value as $key => $row) {
			if (!isset($row['category_id'])) {
				return [Yii::t('app', 'Key "category_id" is missed for row "{i}".', ['i' => $key]), []];
			}

			$categoryId = intval($row['category_id']);
			$category = Category::find()
				->where([
					'category.category_id' => $categoryId,
					'category.deleted_at' => null,
					'category.status' => [Category::STATUS_PUBLISHED, Category::STATUS_HIDDEN]
				])
				->one();
			;

			if (!$category) {
				return [Yii::t('app', 'Cant find category with "category_id" = {value}.', ['value' => $categoryId]), []];
			}
		}

		return null;
	}
}
