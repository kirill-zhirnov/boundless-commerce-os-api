<?php

namespace app\modules\orders\controllers\admin;
use app\components\filters\StrongToken;
use app\components\RestController;
use app\modules\orders\searchModels\OrdersSearch;
use Yii;
use yii\helpers\ArrayHelper;

class OrdersController extends RestController
{
	public function behaviors(): array
	{
		return ArrayHelper::merge(parent::behaviors(), [
			'strongToken' => [
				'class' => StrongToken::class
			]
		]);
	}

	protected function verbs(): array
	{
		return [
			'index' => ['GET'],
			'calc-total' => ['HEAD'],
		];
	}

	public function actionIndex()
	{
		$model = new OrdersSearch();
		return $model->search(Yii::$app->request->queryParams);
	}

	public function actionCalcTotal()
	{
		$model = new OrdersSearch();
		$dataProvider = $model->search(Yii::$app->request->queryParams);

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
}
