<?php

$dbConfig = [
	'managerDb' => [
		'class' => 'yii\db\Connection',
		'dsn' => $_SERVER['MANAGER_DB_DSN'],
		'charset' => 'utf8',
		'enableSchemaCache' => true
	],
];

if (!empty($_SERVER['INSTANCE_DB_DSN_DEV'])) {
	$dbConfig['instanceDb'] = [
		'class' => 'yii\db\Connection',
		'dsn' => $_SERVER['INSTANCE_DB_DSN_DEV'],
		'charset' => 'utf8',
		'enableSchemaCache' => true
	];
}

return $dbConfig;
