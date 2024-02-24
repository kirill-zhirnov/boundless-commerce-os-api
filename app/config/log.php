<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

function specifyLogRoutes(Logger $logger, array $logTo, string $fileName): void
{
	foreach ($logTo as $to) {
		if ($to === 'stream') {
			$logger->pushHandler(new StreamHandler('php://stderr'));
		} elseif ($to === 'file') {
			$logger->pushHandler(new StreamHandler(PATH_ROOT . '/app/runtime/logs/' . $fileName));
		}
	}
}

$logTo = (!empty($_SERVER['LOG_TO'])) ? explode(',', $_SERVER['LOG_TO']) : ['stream'];

$generalLogger = new Logger('boundless_api_general');
specifyLogRoutes($generalLogger, $logTo, 'general.log');

$wixLogger = new Logger('boundless_api_wix');
specifyLogRoutes($wixLogger, $logTo, 'wix.log');

return [
	'log' => [
		'traceLevel' => YII_DEBUG ? 3 : 0,
		'targets' => [
			[
				'class' => 'samdark\log\PsrTarget',
				'logger' => $generalLogger,
				'levels' => ['error'],
				'except' => [
					'yii\web\HttpException:400',
					'yii\web\HttpException:401',
					'yii\web\HttpException:404',
					'yii\web\HttpException:422',
					'yii\web\HttpException:406',
					'wix'
				],
			],
			[
				'class' => 'samdark\log\PsrTarget',
				'logger' => $wixLogger,
				'levels' => ['error'],
				'categories' => ['wix'],
//				'logFile' => '@runtime/logs/wix.log',
				'logVars' => [],
			],
		],
	],
];
