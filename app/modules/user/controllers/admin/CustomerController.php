<?php

namespace app\modules\user\controllers\admin;

use app\components\filters\StrongToken;
use app\components\RestController;
use app\modules\user\formModels\AssignCustomerGroupsForm;
use app\modules\user\searchModels\CustomerSearch;
use yii\helpers\ArrayHelper;
use Yii;
use app\modules\user\traits\CustomerHelpers;
use yii\web\ServerErrorHttpException;

class CustomerController extends RestController
{
	use CustomerHelpers;

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
			'index' => ['GET']
		];
	}

	public function actionIndex()
	{
		$model = new CustomerSearch();
		return $model->search(Yii::$app->request->queryParams);
	}

	public function actionSetGroups($customerId)
	{
		$model = new AssignCustomerGroupsForm();
		$customer = $this->findCustomerByPublicId($customerId);

		$model->setCustomer($customer);
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');
		if ($model->save() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		return $this->findCustomerByPublicId($customerId);
	}
}
