<?php

namespace app\modules\saas\commands;

use app\helpers\Util;
use app\modules\saas\models\AppToken;
use yii\console\Controller;
use app\modules\saas\components\SaasTokenCreator;

class AppTokenController extends Controller
{
	/**
	 * @param string $name
	 * @param string|int|null $exp - expiration in minutes
	 * @throws \Exception
	 */
	public function actionCreate($name, $exp = null)
	{
		$appTokenRow = AppToken::createUniqueClientId();
		$appTokenRow->attributes = [
			'name' => $name,
			'secret' => Util::getRndStr(33, 'letnum', false),
			'require_exp' => (bool) $exp
		];
		$expireInterval = $exp ? new \DateInterval('PT' . $exp . 'M') : null;

		$tokenCreator = new SaasTokenCreator($appTokenRow->client_id, $appTokenRow->secret, $expireInterval);
		$token = $tokenCreator->create();

		if (!$exp) {
			$appTokenRow->permanent_token = $token;
		}

		if (!$appTokenRow->save()) {
			throw new \RuntimeException('Cannot save: ' . print_r($appTokenRow->getErrors(), 1));
		}

		echo "Token created:\n";
		echo "JWT: " . $token . "\n";
		echo "Token row: \n";
		print_r($appTokenRow->attributes);
	}
}
