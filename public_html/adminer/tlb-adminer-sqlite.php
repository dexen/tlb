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

class SqliteTlbDbListing
{
	function databases() : array
	{
		return [
			'../../db.sqlite' => 'db.sqlite',
			'../../config.sqlite' => 'config.sqlite', ];
	}
}

function adminer_object()
{
	require 'plugin.php';

	return new class([new SqlitePasswordHandling(require 'sqlite-password-hash.php'), new SqliteTlbDbListing]) extends AdminerPlugin {

		function XXXloginFormField($name, $heading, $value) {
			return parent::loginFormField($name, $heading, str_replace('value="server"', 'value="sqlite"', $value));
		}

		function XXXdatabase() {
			return $db_pathname;
		}
	};
}

	# a weird work-around for a weird problem of Adminer 4.8
if (!array_key_exists('sqlite', $_GET))
	$_GET['sqlite'] = '';

set_error_handler(null);
