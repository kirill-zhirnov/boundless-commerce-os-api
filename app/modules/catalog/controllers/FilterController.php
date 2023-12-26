<?php

namespace app\modules\catalog\controllers;

use app\components\RestController;
use app\modules\catalog\models\Category;
use app\modules\catalog\models\CategoryProp;
use app\modules\catalog\models\Filter;
use app\modules\catalog\searchModels\FilterSearch;
use Yii;
use yii\web\HttpException;

class FilterController extends RestController
{
	protected function verbs(): array
	{
		return [
			'index' => ['GET', 'HEAD'],
			'view' => ['GET'],
			'by-category' => ['GET'],
		];
	}

	public function actionIndex()
	{
		$model = new FilterSearch();
		return $model->search(Yii::$app->request->queryParams);
	}

	public function actionView($id)
	{
		$filter = Filter::find()
			->withFields()
			->where(['filter_id' => $id])
			->one()
		;

		if (!$filter) {
			throw new HttpException(404);
		}

		return $filter;
	}

	public function actionByCategory($id)
	{
		/** @var CategoryProp $categoryProp */
		$categoryProp = CategoryProp::findOne($id);
		if (!$categoryProp) {
			throw new HttpException(404);
		}

		if (!$categoryProp->use_filter) {
			return null;
		}

		$query = Filter::find()->withFields();
		if ($categoryProp->filter_id) {
			$query->where(['filter_id' => $categoryProp->filter_id]);
		} else {
			$query->where('filter.is_default is true');
		}

		return $query->one();
	}
}
