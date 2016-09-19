<?php
// $config_db = array (
// 'server' => '192.168.1.50',
// 'username' => 'jjshouse',
// 'password' => 'jjshouse',
// 'database' => 'SpiderForGoods'
// );

$config_db = array (
		'localhost' => array (
				'server' => 'localhost',
				'username' => 'root',
				'password' => 'root',
				'database' => 'SpiderForGoods' 
		),
		'192.168.1.50' => array (
				'server' => '192.168.1.50',
				'username' => 'jjshouse',
				'password' => 'jjshouse',
				'database' => 'SpiderForGoods' 
		),
		'192.168.1.51' => array (
				'server' => '192.168.1.50',
				'username' => 'jjshouse',
				'password' => 'jjshouse',
				'database' => 'SpiderForGoods'
		)
);

$config_root_path = array (
		'localhost' => 'd:/goods/',
		'192.168.1.50' => '/var/share/tech/design/SpiderForGoods/' ,
		'192.168.1.51' => '/share/design/SpiderForGoods/'
		
);

$config_proxy = array (
		'localhost' => 'tcp://192.168.1.50:9002',
		'192.168.1.50' => 'tcp://127.0.0.1:9988',
		'192.168.1.51' => 'tcp://127.0.0.1:9988'
);