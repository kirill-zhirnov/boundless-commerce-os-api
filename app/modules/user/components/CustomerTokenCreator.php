<?php

namespace app\modules\user\components;

use app\helpers\Util;
use app\modules\system\models\Setting;
use app\modules\user\models\Person;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Signer\Key\InMemory;

class CustomerTokenCreator
{
	public function __construct(
		protected Person $person,
		protected \DateInterval|null|false $expireIn = null
	) {
		if ($this->expireIn === null) {
			$this->expireIn = new \DateInterval('PT8H');
		}
	}

	public function create(): string
	{
		$configuration = Configuration::forSymmetricSigner(new Sha512(), InMemory::plainText($this->getSecretKey()));

		$builder = $configuration->builder()
			->withClaim('id', $this->person->public_id)
			->withClaim('email', $this->person->email)
			->withClaim('first_name', $this->person->personProfile->first_name)
			->withClaim('last_name', $this->person->personProfile->last_name)
		;

		if ($this->expireIn) {
			$now = SystemClock::fromUTC()->now();
			$builder->expiresAt($now->add($this->expireIn));
		}

		return $builder->getToken($configuration->signer(), $configuration->signingKey())
			->toString()
		;
	}

	public function getSecretKey(): string
	{
		$secret = Setting::getCustomerJWTSecret();
		if (empty($secret)) {
			$secret = Util::getRndStr(34);
			Setting::setCustomerJWTSecret($secret);
		}

		return $secret;
	}
}
