<?php
use yii\helpers\ArrayHelper;

$db = require __DIR__ . '/db.php';
$cache = require __DIR__ . '/cache.php';
$queue = require __DIR__ . '/queue.php';
$log = require __DIR__ . '/log.php';

$config = [
	'id' => 'boundless-api',
	'basePath' => PATH_ROOT . '/app',
	'vendorPath' => PATH_ROOT . '/vendor',
	'bootstrap' => ['log'],
	'language' => $_SERVER['SITE_LANGUAGE'] ?? 'en',
	'aliases' => [
		'@bower' => '@vendor/bower-asset',
		'@npm' => '@vendor/npm-asset',
	],
	'defaultRoute' => 'site/index',
	'components' => [
		'request' => [
			'enableCsrfCookie' => false,
			'cookieValidationKey' => '350cc3779b037f3fa4454d0f45ecb53d',
			'parsers' => [
				'application/json' => 'yii\web\JsonParser',
			]
		],
		//      'response' => [
		//          'format' => \yii\web\Response::FORMAT_JSON
		//      ],
		'user' => [
			'identityClass' => 'app\modules\user\models\User',
			'enableAutoLogin' => false,
			'enableSession' => false
		],
		'errorHandler' => [
			'errorAction' => 'site/error',
		],

		'urlManager' => [
			'enablePrettyUrl' => true,
			'showScriptName' => false,
			'enableStrictParsing' => true,
			'rules' => require(__DIR__ . '/web/urlRules.php'),
		],

		'formatter' => [
			'locale' => $_SERVER['SITE_LOCALE'] ?? 'en_EN',
		],
		'customerUser' => [
			'class' => 'yii\web\User',
			'identityClass' => 'app\modules\user\models\CustomerUser',
			'enableAutoLogin' => false,
			'enableSession' => false
		],
		'saasUser' => [
			'class' => 'yii\web\User',
			'identityClass' => 'app\modules\saas\models\SaasUser',
			'enableAutoLogin' => false,
			'enableSession' => false
		],
		's3Buckets' => [
			'class' => 'app\components\S3Buckets',
		]
	],
	'modules' => [
		'manager' => [
			'class' => 'app\modules\manager\Module',
		],
		'catalog' => [
			'class' => 'app\modules\catalog\Module',
		],
		'user' => [
			'class' => 'app\modules\user\Module',
		],
		'inventory' => [
			'class' => 'app\modules\inventory\Module',
		],
		'system' => [
			'class' => 'app\modules\system\Module',
		],
		'cms' => [
			'class' => 'app\modules\cms\Module',
		],
		'orders' => [
			'class' => 'app\modules\orders\Module',
		],
		'delivery' => [
			'class' => 'app\modules\delivery\Module',
		],
		'payment' => [
			'class' => 'app\modules\payment\Module',
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
$config['components'] = ArrayHelper::merge($config['components'], $queue);
$config['components'] = ArrayHelper::merge($config['components'], $log);

if (YII_ENV_DEV) {
	// configuration adjustments for 'dev' environment
	$config['bootstrap'][] = 'debug';
	$config['modules']['debug'] = [
		'class' => 'yii\debug\Module',
		// uncomment the following to add your IP if you are not connecting from localhost.
		'allowedIPs' => ['*'],
	];

	$config['bootstrap'][] = 'gii';
	$config['modules']['gii'] = [
		'class' => 'yii\gii\Module',
		// uncomment the following to add your IP if you are not connecting from localhost.
		'allowedIPs' => ['*'],
	];
}

return $config;
