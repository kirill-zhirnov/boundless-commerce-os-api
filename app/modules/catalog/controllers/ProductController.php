<?php

namespace app\modules\catalog\controllers;

use app\components\filters\HttpCustomerAuth;
use app\components\RestController;
use app\modules\catalog\components\ProductLoader;
use app\modules\catalog\models\Variant;
use app\modules\catalog\searchModels\FilterFieldsSearch;
use app\modules\catalog\traits\ProductHelpers;
use app\modules\system\models\Lang;
use Yii;
use app\modules\catalog\searchModels\ProductSearch;
use yii\base\DynamicModel;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

class ProductController extends RestController
{
	use ProductHelpers;

	public function behaviors(): array
	{
		return ArrayHelper::merge(parent::behaviors(), [
			'customerAuth' => [
				'class' => HttpCustomerAuth::class,
				'isAuthOptional' => true
			]
		]);
	}

	protected function verbs(): array
	{
		return [
			'index' => ['GET'],
			'calc-total' => ['HEAD'],
			'view' => ['GET', 'HEAD'],
			'variants' => ['GET', 'HEAD'],
			'filter-fields-ranges' => ['POST'],
		//          'create' => ['POST'],
		//          'update' => ['PUT', 'PATCH'],
		//          'delete' => ['DELETE'],
		];
	}

	public function actionIndex()
	{
		$model = new ProductSearch();
		return $model->search(
			$this->transformLegacyQuery(Yii::$app->request->queryParams)
		);
	}

	public function actionCalcTotal()
	{
		$model = new ProductSearch();
		$dataProvider = $model->search(
			$this->transformLegacyQuery(Yii::$app->request->queryParams)
		);

		$pagination = $dataProvider->getPagination();
		$pagination->totalCount = $dataProvider->getTotalCount();

		$links = [];
		foreach ($pagination->getLinks(true) as $rel => $url) {
			$links[] = "<$url>; rel=$rel";
		}

		$this->response->getHeaders()
			->set('X-Pagination-Total-Count', $pagination->totalCount)
			->set('X-Pagination-Page-Count', $pagination->getPageCount())
			->set('X-Pagination-Current-Page', $pagination->getPage() + 1)
			->set('X-Pagination-Per-Page', $pagination->pageSize)
			->set('Link', implode(', ', $links))
		;

		return null;
	}

	public function actionView($id)
	{
		$model = DynamicModel::validateData(Yii::$app->getRequest()->getQueryParams(), [
			['removed', 'in', 'range' => ['all', 'removed']],
			['published_status', 'in', 'range' => ['all', 'hidden']]
		]);

		if ($model->hasErrors()) {
			return $model;
		}

		$productLoader = new ProductLoader($id);
		if ($model->removed) {
			$productLoader->setRemoved($model->removed);
		}

		if ($model->published_status) {
			$productLoader->setPublishedStatus($model->published_status);
		}

		return $productLoader->load();
	}

	public function actionVariants()
	{
		$model = DynamicModel::validateData(ArrayHelper::merge(['product' => ''], Yii::$app->getRequest()->getQueryParams()), [
			[['product'], 'required'],
			[['product'], 'each', 'rule' => ['integer']]
		]);

		if ($model->hasErrors()) {
			return $model;
		}

		$variants = Variant::find()
			->with(['variantTexts' => function (ActiveQuery $query) {
				$query->where(['variant_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->with('inventoryItem')
			->where([
				'product_id' => $model->product,
				'deleted_at' => null
			])
			->all()
		;

		return $variants;
	}

	public function actionFilterFieldsRanges()
	{
		$model = new FilterFieldsSearch();
		$postData = Yii::$app->getRequest()->getBodyParams();
		if (isset($postData['values']) && is_array($postData['values'])) {
			$postData['values'] = $this->transformLegacyQuery($postData['values']);
		}

		if (!$model->initInputData($postData)) {
			return $model;
		}

		return $model->makeFields();
	}
}
