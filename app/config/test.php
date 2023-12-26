<?php

use yii\helpers\ArrayHelper;

$config = ArrayHelper::merge(
	require(__DIR__ . '/web.php'),
	[
		'id' => 'boundless-api-tests',
//		'components' => [
//			'db' => [
//				'class' => 'yii\db\Connection',
//				'dsn' => $_SERVER['TEST_DB_DSN'],
//			]
//		]
	]
);
return $config;
