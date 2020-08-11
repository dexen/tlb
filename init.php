<?php

define('INSTALL_DIR', __DIR__);

require 'lib.php';

spl_autoload_register(function($className) {
	if (strpos($className, '/') !== false)
		throw new LogicException('unsupported character in class name: /');

	$a = explode('\\', $className);

	require 'C/' .implode('/', $a) .'.php';
	return true;
});

update_the_config();
update_the_db();
