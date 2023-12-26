<?php

namespace app\components;

use app\components\filters\HttpCustomerAuth;
use yii\helpers\ArrayHelper;

class CustomerRestController extends RestController
{
	public function behaviors(): array
	{
		return ArrayHelper::merge(parent::behaviors(), [
			'customerAuth' => [
				'class' => HttpCustomerAuth::class
			]
		]);
	}
}
