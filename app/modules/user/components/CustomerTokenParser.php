<?php

namespace app\modules\user\components;

use app\modules\system\models\Setting;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;
use Lcobucci\JWT\Token;
use app\validators\UuidValidator;

class CustomerTokenParser
{
	protected UnencryptedToken $parsedToken;

	public function __construct(
		protected string $token
	) {
	}

	/**
	 * Returns person.public_id stored as "id".
	 */
	public function getId(): string|null
	{
		try {
			$claims = $this->getParsedToken()->claims();
			if ($claims->has('id')) {
				$id = $claims->get('id');

				$validator = new UuidValidator();
				if ($validator->validate($id)) {
					return $id;
				}
			}
		} catch (\Exception $e) {
		}

		return null;
	}

	public function isValid(bool $expRequired = true): bool
	{
		$secretKey = Setting::getCustomerJWTSecret();
		if (!$secretKey) {
			return false;
		}

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
