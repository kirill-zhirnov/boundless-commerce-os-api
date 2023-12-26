<?php

namespace app\modules\user\models;

use app\modules\manager\components\InstanceConnectionBaker;
use Yii;
use app\modules\manager\models\Instance;
use app\modules\user\components\TokenParser;

/**
 * User представляет авторизацию instance - конкретного магазина.
 * Авторизация происходит по токену в заголовке.
 */
class User extends \yii\base\BaseObject implements \yii\web\IdentityInterface
{
	protected Instance $instance;
	protected ApiToken $token;

	/**
	 * {@inheritdoc}
	 */
	public static function findIdentityByAccessToken($token, $type = null): ?static
	{
		$tokenParser = new TokenParser($token);

		$instanceId = $tokenParser->getInstanceId();
		$clientId = $tokenParser->getClientId();

		if (!$instanceId || !$clientId) {
			return null;
		}

		/** @var Instance $instance */
		$instance = Instance::find()
			->where([
				'instance_id' => $instanceId,
				'status' => Instance::STATUS_AVAILABLE
			])
			->one();

		if (!$instance) {
			return null;
		}

		InstanceConnectionBaker::makeByInstance($instance);

		/** @var ApiToken $tokenRow */
		$tokenRow = ApiToken::find()
			->where([
				'client_id' => $clientId,
				'deleted_at' => null
			])
			->one();

		if (!$tokenRow) {
			return null;
		}

		if ($tokenParser->isValid($tokenRow->secret, $tokenRow->require_exp)) {
			$user = new static();
			$user
				->setInstance($instance)
				->setToken($tokenRow);

			return $user;
		}

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function findIdentity($id)
	{
		return null;
		//		throw new \RuntimeException('Not supportable method');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getId(): int|string|null
	{
		//		if ($this->token && $this->instance)
		//			return $this->token->token_id;

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAuthKey()
	{
		return null;
		//		throw new \RuntimeException('Not supportable method');
	}

	/**
	 * {@inheritdoc}
	 */
	public function validateAuthKey($authKey)
	{
		throw new \RuntimeException('Not supportable method');
	}

	public function getInstance(): Instance
	{
		return $this->instance;
	}

	public function setInstance(Instance $instance): self
	{
		$this->instance = $instance;
		return $this;
	}

	public function getToken(): ApiToken
	{
		return $this->token;
	}

	public function setToken(ApiToken $token): self
	{
		$this->token = $token;
		return $this;
	}
}
