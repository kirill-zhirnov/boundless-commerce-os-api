<?php

namespace app\modules\catalog\traits;

use app\modules\catalog\models\Characteristic;
use app\modules\system\models\Lang;
use yii\db\ActiveQuery;
use yii\web\NotFoundHttpException;

trait CharacteristicHelpers
{
	public function findCharacteristicById(int|string $id): Characteristic
	{
		$row = Characteristic::find()
			->with(['characteristicProp'])
			->with(['characteristicTexts' => function (ActiveQuery $query) {
				$query->where(['characteristic_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->with(['characteristicTypeCases.characteristicTypeCaseTexts' => function (ActiveQuery $query) {
				$query->where(['characteristic_type_case_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->where(['characteristic_id' => intval($id)])
			->one()
		;

		if (!$row) {
			throw new NotFoundHttpException('Characteristic not found');
		}

		return $row;
	}
}
