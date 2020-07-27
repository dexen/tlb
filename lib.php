<?php

function td(...$a) {
	foreach ($a as $v)
		echo H(var_export($v, $return = true));
	die('td()');
}

function H(string $str = null) : ?string { return ($str === null) ? $str : htmlspecialchars($str); }
function U(string $str = null) : ?string { return ($str === null) ? $str : rawurlencode($str); }
function HU(string $str = null) : ?string { return ($str === null) ? $str : htmlspecialchars(rawurlencode($str)); }

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

function posts_process(array $a) : array
{
	return array_map('post_process', $a);
}

function post_process(array $rcd) : array
{
	$rcd['_link_text_default'] = (($rcd['_link_text_default']??null)===null) ? $rcd['title'] : $rcd['_link_text_default'];
	$rcd['_url_canonical'] = '?set=post_wiki&slug=' .U($rcd['_url_slug']);
	return $rcd;
}

require 'lib_wiki.php';
