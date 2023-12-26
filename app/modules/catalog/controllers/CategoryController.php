<?php

namespace app\modules\catalog\controllers;

use app\components\RestController;
use app\modules\catalog\models\Category;
use app\modules\catalog\searchModels\CategoryFlatSearch;
use app\modules\catalog\searchModels\CategoryParentsSearch;
use app\modules\catalog\searchModels\CategoryTreeSearch;
use app\modules\system\models\Lang;
use Yii;
use yii\db\ActiveQuery;
use yii\web\HttpException;

class CategoryController extends RestController
{
	protected function verbs()
	{
		return [
			'tree' => ['GET', 'HEAD'],
			'flat' => ['GET', 'HEAD'],
			'item' => ['GET', 'HEAD'],
			'parents' => ['GET'],
			//          'create' => ['POST'],
			//          'update' => ['PUT', 'PATCH'],
			//          'delete' => ['DELETE'],
		];
	}

	public function actionTree()
	{
		$model = new CategoryTreeSearch();
		return $model->search(Yii::$app->request->queryParams);
	}

	public function actionFlat()
	{
		$model = new CategoryFlatSearch();
		return $model->search(Yii::$app->request->queryParams);
	}

	public function actionParents()
	{
		$model = new CategoryParentsSearch();
		return $model->search(Yii::$app->request->queryParams);
	}

	public function actionItem($id, $with_children = null, $with_siblings = null, $with_parents = null, $with_filter = null)
	{
		$query = Category::find()
			->select('category.*')
			->innerJoinWith(['categoryTexts' => function (ActiveQuery $query) {
				$query->where(['category_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->with(['categoryProp', 'image'])
			->withProductsQty()
		;

		if (is_numeric($id)) {
			$query->where(['category.category_id' => $id]);
		} else {
			$query->where(['category_text.url_key' => $id]);
		}

		/** @var Category $category */
		$category = $query->one();
		if (!$category) {
			throw new HttpException(404);
		}

		$category->makeCompiledSeoProps();

		if ($with_children == '1') {
			$flatSearch = new CategoryFlatSearch();
			$childrenDP = $flatSearch->search([
				'parent' => $category->category_id,
				'calc_products' => '1',
				'calc_children' => '1'
			]);
			$childrenDP->pagination->setPageSize(0);
			$category->setChildren($childrenDP->getModels());
		}

		if ($with_siblings == '1') {
			$flatSearch = new CategoryFlatSearch();
			$siblingsDP = $flatSearch->search([
				'parent' => $category->parent_id,
				'calc_products' => '1',
				'calc_children' => '1'
			]);
			$siblingsDP->pagination->setPageSize(0);
			$category->setSiblings($siblingsDP->getModels());
		}

		if ($with_parents == '1') {
			$parentsSearch = new CategoryParentsSearch();
			$category->setParentsCategories(
				$parentsSearch->search(['category' => $category->category_id])
			);
		}

		if ($with_filter == '1') {
			$category->fetchFilter();
		}

		return $category;
	}
}
