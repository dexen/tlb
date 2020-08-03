<?php

function td(...$a) {
	echo '<pre>';
	foreach ($a as $v)
		echo H(var_export($v, $return = true));
	die('td()');
}

function tp(...$a) { foreach ($a as $v) var_dump($v); echo 'tp()'; }

function H(string $str = null) : ?string { return ($str === null) ? $str : htmlspecialchars($str); }
function U(string $str = null) : ?string { return ($str === null) ? $str : rawurlencode($str); }
function HU(string $str = null) : ?string { return ($str === null) ? $str : htmlspecialchars(rawurlencode($str)); }

function lf(string $str = null) : ?string { return is_null($str) ? null : str_replace("\r\n", "\n", $str);}

function noz($v)
{
	if (is_array($v))
		return array_map('noz', $v);
	else if ($v === null)
		return null;
	else if ($v === '')
		return null;
	else if (is_scalar($v))
		return $v;
	else if (is_object($v))
throw new Exception('an object, don\'t know what to do');
	else
throw new Exception('other type, don\'t know wha to do');
}

function db_pdo() : DB
{
	return new DB('db.sqlite');
}

function config_db_pdo() : DB
{
	return new DB('config.sqlite');
}

function posts_process(array $a) : array
{
	return array_map('post_process', $a);
}

function post_process(array $rcd) : array
{
	$rcd['_link_text_default'] = $rcd['_link_text_default'] ?? $rcd['title'] ?? $rcd['_link_text_default'] ?? $rcd['_url_slug'];
	$rcd['_url_canonical'] = '?set=post_wiki&slug=' .U($rcd['_url_slug']);
	return $rcd;
}

function http_cache_prevent()
{
	header('Cache-Control: no-store, no-cache, must-revalidate');
	header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
}

function array_one(array $a)
{
	switch (count($a)) {
	case 0:
		throw new Exception('expected exactly one item, got none');
	default:
		throw new Exception('expected exactly one item, got multiple');
	case 1:
		return array_shift($a); }
}

require 'lib_wiki.php';
require 'lib_tlb.php';
