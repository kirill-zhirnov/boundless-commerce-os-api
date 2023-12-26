<?php

namespace app\modules\saas\models;

use app\modules\user\components\TokenParser;

class SaasUser extends \yii\base\BaseObject implements \yii\web\IdentityInterface
{
	protected AppToken $token;

	public static function findIdentityByAccessToken($token, $type = null): ?static
	{
		$tokenParser = new TokenParser($token);

		$clientId = $tokenParser->getClientId();

		if (!$clientId) {
			return null;
		}

		/** @var AppToken $appTokenRow */
		$appTokenRow = AppToken::find()
			->where([
				'client_id' => $clientId,
			])
			->one()
		;
		if (!$appTokenRow) {
			return null;
		}

		if ($tokenParser->isValid($appTokenRow->secret, $appTokenRow->require_exp)) {
			$user = new static();
			$user
				->setToken($appTokenRow);

			return $user;
		}

		return null;
	}

	public static function findIdentity($id)
	{
		return null;
	}

	public function getId(): int|string|null
	{
		return null;
	}

	public function getAuthKey()
	{
		return null;
	}

	public function validateAuthKey($authKey)
	{
		throw new \RuntimeException('Not supportable method');
	}

	public function getToken(): AppToken
	{
		return $this->token;
	}

	public function setToken(AppToken $token): self
	{
		$this->token = $token;
		return $this;
	}
}
