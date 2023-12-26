<?php

namespace app\modules\user\components;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\Clock\SystemClock;

class TokenCreator
{
	public function __construct(
		protected int $instanceId,
		protected string $clientId,
		protected string $secretKey,
		protected ?\DateInterval $expireIn = null
	) {
	}

	public function create(): string
	{
		$configuration = Configuration::forSymmetricSigner(new Sha512(), InMemory::plainText($this->secretKey));

		$builder = $configuration->builder()
			->withClaim('iId', $this->instanceId)
			->withClaim('cId', $this->clientId)
		;

		if ($this->expireIn) {
			$now = SystemClock::fromUTC()->now();
			$builder->expiresAt($now->add($this->expireIn));
		}

		return $builder->getToken($configuration->signer(), $configuration->signingKey())
			->toString()
		;
	}
}
