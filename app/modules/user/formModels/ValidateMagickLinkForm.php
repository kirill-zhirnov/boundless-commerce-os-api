<?php

namespace app\modules\user\formModels;

use app\modules\manager\models\Instance;
use app\modules\user\models\PersonToken;
use yii\base\Model;

class ValidateMagickLinkForm extends Model
{
	public $query_string;

	protected ?Instance $instance;

	public function rules(): array
	{
		return [
			[
				['query_string'],
				'required'
			],
			['query_string', 'string'],
		];
	}

	public function process(): array|false
	{
		if (!isset($this->instance)) {
			throw new \RuntimeException('Instance should be set prior calling this func.');
		}

		if (!$this->validate()) {
			return false;
		}

		parse_str(ltrim($this->query_string, '?'), $params);
		$sign = $params['sign'] ?? '';
		$paramsWithoutSign = array_filter($params, fn ($val, $key) => $key != 'sign', ARRAY_FILTER_USE_BOTH);

		$correctSign = PersonToken::signParams($this->instance->getAuthSalt(), $paramsWithoutSign);
		if ($sign !== $correctSign) {
			$this->addError('query_string', 'Signature in the query_string is incorrect.');
			return false;
		}

		/** @var PersonToken $personToken */
		$personToken = PersonToken::find()
			->where([
				'token_id' => $params['id'] ?? '0',
				'token_1' => $params['token1'] ?? '',
				'token_2' => $params['token2'] ?? '',
				'type' => PersonToken::TYPE_MAGICK_LINK,
			])
			->andWhere('valid_till >= now() or valid_till is null')
			->with('person')
			->one()
		;

		if (!$personToken) {
			$this->addError('query_string', 'Toke is expired or not found.');
			return false;
		}

		return [
			'person' => $personToken->person,
			'authToken' => $personToken->person->createAuthToken()
		];
	}

	public function setInstance(?Instance $instance): self
	{
		$this->instance = $instance;
		return $this;
	}
}
