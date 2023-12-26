<?php

namespace app\modules\user\components;

use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Validation\Validator;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

class TokenParser
{
	protected string $token;

	protected UnencryptedToken $parsedToken;

	public function __construct(string $token)
	{
		$this->token = $token;
	}

	public function getInstanceId(): null|int
	{
		try {
			$claims = $this->getParsedToken()->claims();
			if ($claims->has('iId')) {
				$instanceId = $claims->get('iId');

				if (is_numeric($instanceId)) {
					return intval($instanceId);
				}
			}
		} catch (\Exception $e) {
		}

		return null;
	}

	public function getClientId(): null|string
	{
		try {
			$claims = $this->getParsedToken()->claims();
			if ($claims->has('cId')) {
				return $claims->get('cId');
			}
		} catch (\Exception $e) {
		}

		return null;
	}

	public function isValid(string $secretKey, bool $expRequired): bool
	{
		try {
			$parsedToken = $this->getParsedToken();

			$validator = new Validator();
			$validator->assert($parsedToken, ...[new SignedWith(new Sha512(), InMemory::plainText($secretKey))]);

			if ($expRequired) {
				$now = SystemClock::fromUTC()->now()->modify('-5 minutes');

				return (
					$parsedToken->claims()->has(Token\RegisteredClaims::EXPIRATION_TIME)
					&& !$parsedToken->isExpired($now)
				);
			} else {
				return true;
			}
		} catch (\Exception $e) {
			return false;
		}
	}

	public function getParsedToken(): UnencryptedToken
	{
		if (!isset($this->parsedToken)) {
			$decoder = new JoseEncoder();
			$parser = new Parser($decoder);

			$this->parsedToken = $parser->parse($this->token);
		}

		return $this->parsedToken;
	}
}
