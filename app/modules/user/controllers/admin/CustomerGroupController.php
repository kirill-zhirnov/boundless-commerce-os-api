<?php

namespace app\modules\user\controllers\admin;

use app\components\filters\StrongToken;
use app\components\RestController;
use app\modules\user\formModels\CustomerGroupForm;
use app\modules\user\searchModels\CustomerGroupSearch;
use yii\helpers\ArrayHelper;
use Yii;
use app\modules\user\models\CustomerGroup;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

class CustomerGroupController extends RestController
{
	public function behaviors(): array
	{
		return ArrayHelper::merge(parent::behaviors(), [
			'strongToken' => [
				'class' => StrongToken::class
			]
		]);
	}

	public function actionIndex()
	{
		$model = new CustomerGroupSearch();
		return $model->search(Yii::$app->request->queryParams);
	}

	public function actionForm($id = null)
	{
		$model = new CustomerGroupForm();
		if ($id) {
			$model->setCustomerGroup($this->findCustomerGroup($id));
		}

		$model->load(Yii::$app->getRequest()->getBodyParams(), '');
		if ($model->save() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors()) {
			return $model;
		}

		return $model->getCustomerGroup();
	}

	public function actionDelete($id)
	{
		$group = $this->findCustomerGroup($id);
		$group->safeDelete();

		/** @var \app\components\InstancesQueue $queue */
		$queue = Yii::$app->queue;
		$queue->modelArchived(CustomerGroup::class, [$group->group_id]);

		return true;
	}

	protected function findCustomerGroup(int|string $id): CustomerGroup
	{
		$model = CustomerGroup::findOne(['group_id' => $id, 'deleted_at' => null]);
		if (!$model) {
			throw new NotFoundHttpException('Customer group not found');
		}
		return $model;
	}
}
