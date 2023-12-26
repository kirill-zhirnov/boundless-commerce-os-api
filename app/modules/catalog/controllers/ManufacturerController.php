<?php

namespace app\modules\catalog\controllers;

use app\components\RestController;
use app\modules\catalog\models\Manufacturer;
use app\modules\catalog\searchModels\ManufacturerSearch;
use app\modules\system\models\Lang;
use Yii;
use yii\db\ActiveQuery;
use yii\web\HttpException;

class ManufacturerController extends RestController
{
	public function actionIndex()
	{
		$model = new ManufacturerSearch();
		return $model->search(Yii::$app->request->queryParams);
	}

	public function actionView($id)
	{
		$query = Manufacturer::find()
			->innerJoinWith(['manufacturerTexts' => function (ActiveQuery $query) {
				$query->where(['manufacturer_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->with(['image'])
		;

		if (is_numeric($id)) {
			$query->where(['manufacturer.manufacturer_id' => $id]);
		} else {
			$query->where(['manufacturer_text.url_key' => $id]);
		}

		$row = $query->one();
		if (!$row) {
			throw new HttpException(404);
		}

		return $row;
	}
}
