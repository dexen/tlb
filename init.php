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

(function() {
	$rr = function($v) {
		if (is_array($v))
			return array_map($rr, $v);
		else if (is_string($v))
			return str_replace("\r", '', $v);
		else
			throw new Exception('unsupported data type');
	};
	$_POST = array_map($rr, $_POST);
})();
