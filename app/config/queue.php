<?php

return [
	'queue' => [
		'class' => \app\components\InstancesQueue::class,
		'host' => $_SERVER['RABBIT_HOST'],
		'port' => $_SERVER['RABBIT_PORT'],
		'user' => $_SERVER['RABBIT_USER'],
		'password' => $_SERVER['RABBIT_PASS'],
	]
];
