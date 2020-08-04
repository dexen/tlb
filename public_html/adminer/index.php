<?php

ini_set('session.save_path', realpath(__DIR__ .'/' .'../../session'));

function td(...$a)
{
	foreach ($a as $v)
		echo htmlspecialchars(var_export($v, true));
	die('td()');
}

class SqlitePasswordHandling
{
	protected $password_hash;

	function __construct(string $password_hash)
	{
		$this->password_hash = $password_hash;
	}

	function credentials() {
		$password = get_password();
		return array(SERVER, $_GET["username"], (password_verify($password, $this->password_hash) ? "" : $password));
	}

	function login($login, $password) {
		if ($password != "") {
			return true;
		}
	}
}

function adminer_object()
{
	require 'plugin.php';

	return new class([new SqlitePasswordHandling(require 'sqlite-password-hash.php')]) extends AdminerPlugin {

		function XXXloginFormField($name, $heading, $value) {
			return parent::loginFormField($name, $heading, str_replace('value="server"', 'value="sqlite"', $value));
		}

		function XXXdatabase() {
			return $db_pathname;
		}
	};
}

set_error_handler(null);

require 'adminer-sqlite.php';
