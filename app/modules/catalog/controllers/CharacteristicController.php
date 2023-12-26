<?php

namespace app\modules\catalog\controllers;

use app\components\RestController;
use app\modules\catalog\models\Characteristic;
use app\modules\catalog\searchModels\CharacteristicSearch;
use app\modules\system\models\Lang;
use yii\db\ActiveQuery;
use yii\web\HttpException;
use Yii;

class CharacteristicController extends RestController
{
	protected function verbs(): array
	{
		return [
			'view' => ['GET', 'HEAD'],
		];
	}

	public function actionIndex()
	{
		$model = new CharacteristicSearch();
		return $model->search(
			Yii::$app->request->queryParams
		);
	}

	public function actionView($id)
	{
		$query = Characteristic::find()
			->with(['characteristicProp'])
			->with(['characteristicTexts' => function (ActiveQuery $query) {
				$query->where(['characteristic_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->with(['characteristicTypeCases' => function (ActiveQuery $query) {
				$query->orderBy(['characteristic_type_case.sort' => SORT_ASC]);
			}])
			->with(['characteristicTypeCases.characteristicTypeCaseTexts' => function (ActiveQuery $query) {
				$query->where(['characteristic_type_case_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
		;

		if (is_numeric($id)) {
			$query->where(['characteristic_id' => $id]);
		} else {
			$query->where(['alias' => $id]);
		}

		$row = $query->one();
		if (!$row) {
			throw new HttpException(404);
		}

		return $row;
	}
}
