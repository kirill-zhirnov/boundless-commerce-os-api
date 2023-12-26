<?php

define('PATH_ROOT', dirname(__DIR__));

$config = require_once PATH_ROOT . '/app/inc/bootstrap.php';

(new yii\web\Application($config))->run();
