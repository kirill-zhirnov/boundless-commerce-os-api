<?php

namespace app\modules\catalog\activeQueries;

use app\modules\system\models\Lang;
use yii\db\ActiveQuery;

class FilterQuery extends ActiveQuery
{
	public function withFields(): self
	{
		$this->with(['filterFields' => function (ActiveQuery $query) {
			$query->orderBy(['filter_field.sort' => SORT_ASC]);
		}])
		->with(['filterFields.characteristic'])
		->with(['filterFields.characteristic.characteristicTexts' => function (ActiveQuery $query) {
			$query->where(['characteristic_text.lang_id' => Lang::DEFAULT_LANG]);
		}])
		->with(['filterFields.characteristic.characteristicTypeCases' => function (ActiveQuery $query) {
			$query->orderBy(['characteristic_type_case.sort' => SORT_ASC]);
		}])
		->with(['filterFields.characteristic.characteristicTypeCases.characteristicTypeCaseTexts' => function (ActiveQuery $query) {
			$query->where(['characteristic_type_case_text.lang_id' => Lang::DEFAULT_LANG]);
		}]);

		return $this;
	}
}
