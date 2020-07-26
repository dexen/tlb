<?php

function td(...$a) {
	foreach ($a as $v)
		echo H(var_export($v, $return = true));
	die('td()');
}

function H(string $str = null) : ?string { return ($str === null) ? $str : htmlspecialchars($str); }
function U(string $str = null) : ?string { return ($str === null) ? $str : rawurlencode($str); }
function HU(string $str = null) : ?string { return ($str === null) ? $str : htmlspecialchars(rawurlencode($str)); }

function db_pdo() : DB
{
	return new DB('db.sqlite');
}

