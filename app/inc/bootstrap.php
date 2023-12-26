<?php

require(PATH_ROOT . '/vendor/autoload.php');

if (file_exists(PATH_ROOT . '/.env')) {
	$dotenv = Dotenv\Dotenv::createImmutable(PATH_ROOT);
	$dotenv->load();
}

bcscale(2);

defined('YII_DEBUG') or define('YII_DEBUG', isset($_SERVER['YII_DEBUG']) ? boolval($_SERVER['YII_DEBUG']) : false);
defined('YII_ENV') or define('YII_ENV', isset($_SERVER['YII_ENV']) ? $_SERVER['YII_ENV'] : 'prod');

require(PATH_ROOT . '/vendor/yiisoft/yii2/Yii.php');

if (!isset($configFile)) {
	$configFile = 'web.php';
}

//Yii::setAlias('@modules', PATH_ROOT . '/app/modules');
//Yii::setAlias('@helpers', PATH_ROOT . '/app/helpers');
//Yii::setAlias('@root', PATH_ROOT);

$config = require(PATH_ROOT . '/app/config/' . $configFile);

return $config;
