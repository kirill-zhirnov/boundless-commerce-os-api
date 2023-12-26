<?php

namespace app\modules\manager\components;

use Yii;
use app\modules\manager\models\Instance;
use yii\db\Connection;

class InstanceConnectionBaker
{
	public static function make(int $instanceId, bool $setInApp = true): Connection
	{
		/** @var Instance|null $instance */
		$instance = Instance::findOne($instanceId);
		if (!$instance) {
			throw new \RuntimeException('Cannot find instance with ID: ' . $instanceId);
		}

		return self::makeByInstance($instance, $setInApp);
	}

	public static function makeByInstance(Instance $instance, bool $setInApp = true): Connection
	{
		$host = $_SERVER['INSTANCE_DB_HOST'] ?? $instance->config['db']['config']['host'];
		$connection = new Connection([
			'dsn' => 'pgsql:host=' . $host . ';dbname=' . $instance->config['db']['name'],
			'username' => $instance->config['db']['user'],
			'password' => $instance->config['db']['pass'],
			'charset' => 'utf8',
			'enableSchemaCache' => true
		]);

		if ($setInApp) {
			Yii::$app->set('instanceDb', $connection);
		}

		return $connection;
	}
}
