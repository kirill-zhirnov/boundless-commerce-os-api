<?php

$cache = match ($_SERVER['CACHE_TYPE'] ?? null) {
	'memcached' => [
		'class' => 'yii\caching\MemCache',
		'defaultDuration' => 3600,
		'useMemcached' => true,
		'username' => isset($_SERVER['CACHE_MEMCACHED_USER']) ? $_SERVER['CACHE_MEMCACHED_USER'] : null,
		'password' => isset($_SERVER['CACHE_MEMCACHED_PASS']) ? $_SERVER['CACHE_MEMCACHED_PASS'] : null,
		'servers' => [
			[
				'host' => $_SERVER['CACHE_MEMCACHED_HOST'],
				'port' => $_SERVER['CACHE_MEMCACHED_PORT'],
			]
		],
		'options' => [
			//options needs for Node.js compatibility - so we know exactly how to hash keys.
			Memcached::OPT_HASH => Memcached::HASH_MD5
		]
	],
	default => [
		'class' => 'yii\caching\FileCache',
	]
};

return $cache ? ['cache' => $cache] : [];
