<?php

use yii\helpers\ArrayHelper;

$db = require __DIR__ . '/db.php';
$cache = require __DIR__ . '/cache.php';

$config = [
	'id' => 'basic-console',
	'basePath' => PATH_ROOT . '/app',
	'vendorPath' => PATH_ROOT . '/vendor',
	'bootstrap' => [
		'log',
		'user',
		'saas',
		'files'
	],
	'components' => [
		'log' => [
			'targets' => [
				[
					'class' => 'yii\log\FileTarget',
					'levels' => ['error', 'warning'],
					'logVars' => [],
					'except' => [],
				],
			],
		],
		's3Buckets' => [
			'class' => 'app\components\S3Buckets',
		]
	],
	'modules' => [
		'user' => [
			'class' => 'app\modules\user\Module',
		],
		'saas' => [
			'class' => 'app\modules\saas\Module',
		],
		'files' => [
			'class' => 'app\modules\files\Module',
		],
	],
];

$config['components'] = ArrayHelper::merge($config['components'], $db);
$config['components'] = ArrayHelper::merge($config['components'], $cache);

return $config;
