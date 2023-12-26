<?php

namespace app\modules\saas\components;

use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Signer\Key\InMemory;

class SaasTokenCreator
{
	public function __construct(
		protected string $clientId,
		protected string $secretKey,
		protected ?\DateInterval $expireIn = null
	) {
	}

	public function create(): string
	{
		$configuration = Configuration::forSymmetricSigner(new Sha512(), InMemory::plainText($this->secretKey));

		$builder = $configuration->builder()
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
