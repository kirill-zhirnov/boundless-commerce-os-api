<?php

namespace app\components\actions;

use Yii;

class OptionsAction extends \yii\base\Action
{
	public array $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

	public function run($id = null)
	{
		if (Yii::$app->getRequest()->getMethod() !== 'OPTIONS') {
			Yii::$app->getResponse()->setStatusCode(405);
		}

		$headers = Yii::$app->getResponse()->getHeaders();
		$headers->set('Allow', implode(', ', $this->allowedMethods));
		$headers->set('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));
		$headers->set('Access-Control-Allow-Headers', 'Authorization, X-Customer, Content-Type, Pragma, Cache-Control, Expires');
		$headers->set('Access-Control-Allow-Origin', '*');
		$headers->set('Access-Control-Max-Age', '3600');
	}
}
