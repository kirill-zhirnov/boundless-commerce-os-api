<?php

namespace app\modules\orders\controllers\customer;

use app\components\CustomerRestController;
use app\modules\orders\searchModels\CustomerOrdersSearch;
use Yii;

class MyOrdersController extends CustomerRestController
{
	protected function verbs(): array
	{
		return [
			'index' => ['GET']
		];
	}

	public function actionIndex()
	{
		$model = new CustomerOrdersSearch();

		/** @var \yii\web\User $customerUser */
		$customerUser = Yii::$app->customerUser;
		$model->setCustomer($customerUser->getIdentity()->getPerson());

		return $model->search(Yii::$app->request->queryParams);
	}
}
