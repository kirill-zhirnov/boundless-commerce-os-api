<?php

namespace app\modules\catalog\validators;
use app\modules\catalog\models\Collection;
use yii\validators\Validator;
use Yii;

class ProductCollectionsValidator extends Validator
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
			return [Yii::t('app', 'Collections should be an array of object with "collection_id".'), []];
		}

		foreach ($value as $key => $row) {
			if (!isset($row['collection_id'])) {
				return [Yii::t('app', 'Key "collection_id" is missed for row "{i}".', ['i' => $key]), []];
			}

			$collectionId = intval($row['collection_id']);
			$collection = Collection::find()
				->where([
					'collection_id' => $collectionId,
					'deleted_at' => null,
				])
				->one();
			;

			if (!$collection) {
				return [Yii::t('app', 'Cant find collection with "collection_id" = {value}.', ['value' => $row['collection_id']]), []];
			}
		}

		return null;
	}
}
