<?php

namespace app\modules\user\commands;

use app\helpers\Util;
use app\modules\manager\components\InstanceConnectionBaker;
use app\modules\user\components\TokenCreator;
use app\modules\user\models\ApiToken;
use yii\console\Controller;
use DateInterval;

class ApiTokenController extends Controller
{
	/**
	 * @param string $name
	 * @param string|int $instanceId
	 * @param string|int|null $exp - expiration in minutes
	 * @throws \Exception
	 */
	public function actionCreate($name, $instanceId, $exp = null)
	{
		$instanceId = intval($instanceId);
		InstanceConnectionBaker::make($instanceId);

		$tokenRow = ApiToken::createUniqueClientId();
		$tokenRow->attributes = [
			'name' => $name,
			'secret' => Util::getRndStr(33, 'letnum', false),
			'require_exp' => (bool)$exp
		];

		$expireInterval = $exp ? new DateInterval('PT' . $exp . 'M') : null;
		$tokenCreator = new TokenCreator($instanceId, $tokenRow->client_id, $tokenRow->secret, $expireInterval);
		$token = $tokenCreator->create();

		if (!$exp) {
			$tokenRow->permanent_token = $token;
		}

		if (!$tokenRow->save()) {
			throw new \RuntimeException('Cannot save: ' . print_r($tokenRow->getErrors(), 1));
		}

		echo "Token created:\n";
		echo "JWT: " . $token . "\n";
		echo "Token row: \n";
		print_r($tokenRow->attributes);
	}
}
