<?php

namespace app\modules\catalog\controllers;

use app\components\RestController;
use app\modules\catalog\models\CommodityGroup;
use app\modules\catalog\models\VwCharacteristicGrid;
use app\modules\catalog\searchModels\CharacteristicSearch;
use app\modules\catalog\searchModels\CommodityGroupSearch;
use app\modules\system\models\Lang;
use Yii;
use yii\db\ActiveQuery;
use yii\web\HttpException;

class CommodityGroupController extends RestController
{
	protected function verbs(): array
	{
		return [
			'index' => ['GET', 'HEAD'],
			'view' => ['GET', 'HEAD'],
//			'characteristics' => ['GET', 'HEAD'],
		];
	}

	public function actionIndex()
	{
		$model = new CommodityGroupSearch();
		return $model->search(Yii::$app->request->queryParams);
	}

	public function actionView($id)
	{
		return $this->findCommodityGroup($id);
	}

//	public function actionCharacteristics($id)
//	{
//		$group = $this->findCommodityGroup($id);
//
//		$model = new CharacteristicSearch();
//		$model->setCommodityGroup($group);
//
//		return $model->search(Yii::$app->request->queryParams);
//	}

	protected function findCommodityGroup($id): CommodityGroup
	{
		$row = CommodityGroup::find()
			->with(['commodityGroupTexts' => function (ActiveQuery $query) {
				$query->where(['commodity_group_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->with(['characteristics' => function (ActiveQuery $query) {
				$query->orderBy(['sort' => SORT_ASC]);
			}])
			->with(['characteristics.characteristicTexts' => function (ActiveQuery $query) {
				$query->where(['characteristic_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->with(['characteristics.characteristicProp'])
			->with(['characteristics.characteristicTypeCases' => function (ActiveQuery $query) {
				$query->orderBy(['sort' => SORT_ASC]);
			}])
			->with(['characteristics.characteristicTypeCases.characteristicTypeCaseTexts' => function (ActiveQuery $query) {
				$query->where(['characteristic_type_case_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->where(['group_id' => $id])
			->one()
		;

		if (!$row) {
			throw new HttpException(404);
		}

		return $row;
	}
}
