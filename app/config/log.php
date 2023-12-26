<?php

return [
	'log' => [
		'traceLevel' => YII_DEBUG ? 3 : 0,
		'targets' => [
			[
				'class' => 'yii\log\FileTarget',
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
				'class' => 'yii\log\FileTarget',
				'levels' => ['error'],
				'categories' => ['wix'],
				'logFile' => '@runtime/logs/wix.log',
				'logVars' => [],
			],
		],
	],
];
