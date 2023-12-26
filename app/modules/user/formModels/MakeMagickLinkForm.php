<?php

namespace app\modules\user\formModels;

use app\helpers\Util;
use app\modules\manager\models\Instance;
use app\modules\user\models\Person;
use app\modules\user\models\PersonToken;
use app\validators\UuidValidator;
use yii\base\Model;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

class MakeMagickLinkForm extends Model
{
	public $user_id;
	public $expire_in;
	public $url_prefix;
	public $extra_params;

	protected ?Person $person;
	protected ?Instance $instance;

	public function rules(): array
	{
		return [
			[['expire_in', 'user_id'], 'required'],
			['user_id', UuidValidator::class],
			['user_id', 'validateUserId'],
			['expire_in', 'in', 'range' => ['1day', '1week', '1month']],
			['url_prefix', 'string', 'max' => 1000],
			['extra_params', 'each', 'rule' => ['string']],
			['extra_params', 'validateExtraParams'],
		];
	}

	public function makeLink(): array|false
	{
		if (!isset($this->instance)) {
			throw new \RuntimeException('Person and Instance should be set prior making link.');
		}

		if (!$this->validate()) {
			return false;
		}

		$token = $this->makePersonToken();

		$queryParams = ArrayHelper::merge([
			'id' => $token->token_id,
			'token1' => $token->token_1,
			'token2' => $token->token_2,
		], is_array($this->extra_params) ? $this->extra_params : []);
		$queryParams['sign'] = PersonToken::signParams($this->instance->getAuthSalt(), $queryParams);

		$queryString = http_build_query($queryParams);

		return [
			'url' => $this->url_prefix . '?' . $queryString,
			'queryString' => $queryString,
			'queryParams' => $queryParams
		];
	}

	protected function makePersonToken(): PersonToken
	{
		switch ($this->expire_in) {
			case '1day':
				$validTill = new Expression("now() + interval '1hour'");
				break;
			case '1week':
				$validTill = new Expression("now() + interval '1week'");
				break;
			case '1month':
				$validTill = new Expression("now() + interval '1month'");
				break;
		}

		$token = new PersonToken();
		$token->attributes = [
			'person_id' => $this->person->person_id,
			'type' => PersonToken::TYPE_MAGICK_LINK,
			'token_1' => Util::getRndStr(30, 'letnum', false),
			'token_2' => Util::getRndStr(10, 'numbers'),
			'valid_till' => $validTill
		];
		$token->save(false);
		$token->refresh();

		return $token;
	}

	public function validateExtraParams()
	{
		if (is_array($this->extra_params)) {
			$blockedKeys = ['id', 'token1', 'token2', 'sign'];
			foreach ($blockedKeys as $key) {
				if (array_key_exists($key, $this->extra_params)) {
					$this->addError('extra_params', "Key '". $key . "' is forbidden.");
					return;
				}
			}
		}
	}

	public function setPerson(?Person $person): MakeMagickLinkForm
	{
		$this->person = $person;
		return $this;
	}

	public function setInstance(?Instance $instance): MakeMagickLinkForm
	{
		$this->instance = $instance;
		return $this;
	}

	public function validateUserId()
	{
		$this->person = Person::find()->where(['public_id' => $this->user_id])->one();

		if (!$this->person) {
			$this->addError('user_id', 'User not found');
		}
	}
}
