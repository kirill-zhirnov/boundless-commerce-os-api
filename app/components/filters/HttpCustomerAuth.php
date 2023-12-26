<?php

namespace app\components\filters;

use yii\filters\auth\HttpHeaderAuth;
use Yii;

class HttpCustomerAuth extends HttpHeaderAuth
{
	public $header = 'X-Customer';

	public $optional = ['options'];

	public bool $isAuthOptional = false;

	public function beforeAction($action)
	{
		$this->user = Yii::$app->customerUser;

		return parent::beforeAction($action);
	}

	protected function isOptional($action)
	{
		if ($this->isAuthOptional) {
			return true;
		}

		return parent::isOptional($action);
	}
}
