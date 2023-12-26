<?php

namespace app\components;

use app\components\filters\HttpSaasAuth;
use yii\filters\Cors;
use yii\helpers\ArrayHelper;
use yii\rest\Controller;

class SaasRestController extends Controller
{
	public function behaviors(): array
	{
		return ArrayHelper::merge(parent::behaviors(), [
			'corsFilter' => [
				'class' => Cors::class,
				'cors' => [
					'Origin' => ['*'],
//					'Access-Control-Request-Method' => ['POST', 'PUT', 'GET'],
					'Access-Control-Max-Age' => 3600,
					'Access-Control-Expose-Headers' => [
						'X-Pagination-Current-Page',
						'X-Pagination-Total-Count',
						'X-Pagination-Page-Count',
						'X-Pagination-Page-Count',
						'X-Pagination-Current-Page',
						'X-Pagination-Per-Page'
					],
				],
				'except' => ['options']
			],
			'authenticator' => [
				'class' => HttpSaasAuth::class,
				'except' => ['options']
			],
		]);
	}
}
