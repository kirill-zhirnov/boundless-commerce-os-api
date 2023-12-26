<?php

namespace app\modules\user\formModels;

use app\modules\user\models\Person;
use app\modules\user\models\PersonToken;
use yii\helpers\ArrayHelper;
use app\components\InstancesQueue;
use Yii;

class RestorePassEmailForm extends MakeMagickLinkForm
{
	public $email;

	public function rules(): array
	{
		return [
			[['email', 'url_prefix'], 'required'],
			['email', 'email'],
			['email', 'validateEmail'],
			['url_prefix', 'string', 'max' => 2000],
			['extra_params', 'each', 'rule' => ['string']],
			['extra_params', 'validateExtraParams'],
		];
	}

	public function sendLink(): bool
	{
		if (!isset($this->instance)) {
			throw new \RuntimeException('Person and Instance should be set prior making link.');
		}

		if (!$this->validate()) {
			return false;
		}

		$this->expire_in = '1month';
		$token = $this->makePersonToken();
		$queryParams = ArrayHelper::merge([
			'id' => $token->token_id,
			'token1' => $token->token_1,
			'token2' => $token->token_2,
		], is_array($this->extra_params) ? $this->extra_params : []);
		$queryParams['sign'] = PersonToken::signParams($this->instance->getAuthSalt(), $queryParams);

		$queryString = http_build_query($queryParams);
		$restoreUrl = $this->url_prefix . '?' . $queryString;

		/** @var InstancesQueue $queue */
		$queue = Yii::$app->queue;
		$queue->sendMail(InstancesQueue::MAIL_RESTORE_PASSWORD, [
			'username' => $this->person->personProfile->getFullName(),
			'email' => $this->person->email,
			'restoreUrl' => $restoreUrl
		]);

		return true;
	}

	public function validateEmail()
	{
		$this->person = Person::find()
			->where([
				'email' => mb_strtolower($this->email),
				'status' => Person::STATUS_PUBLISHED,
				'deleted_at' => null
			])
			->andWhere('registered_at is not null')
			->one()
		;

		if (!$this->person) {
			$this->addError('email', 'User not found');
		}
	}
}
