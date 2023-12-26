<?php

namespace app\components\filters;

use Yii;
use yii\filters\auth\HttpHeaderAuth;

class HttpSaasAuth extends HttpHeaderAuth
{
	/**
	 * {@inheritdoc}
	 */
	public $header = 'Authorization';
	/**
	 * {@inheritdoc}
	 */
	public $pattern = '/^Bearer\s+(.*?)$/';
	/**
	 * @var string the HTTP authentication realm
	 */
	public $realm = 'api';

	public function beforeAction($action)
	{
		$this->user = Yii::$app->saasUser;

		return parent::beforeAction($action);
	}

	/**
	 * {@inheritdoc}
	 */
	public function challenge($response)
	{
		$response->getHeaders()->set('WWW-Authenticate', "Bearer realm=\"{$this->realm}\"");
	}
}
