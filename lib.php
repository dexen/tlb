<?php

function td(...$a) {
	echo '<pre>';
	foreach ($a as $v)
		echo H(var_export($v, $return = true)) ."\n--\n";
	echo "td();";
	die(1);
}

function tp(...$a) { echo '<pre>'; foreach ($a as $v) var_dump($v); echo '</pre>'; echo 'tp()'; return $a[0]; }

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
	static $conn = null;
	if ($conn === null)
		$conn = new DB('db.sqlite');
	return $conn;
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
	$rcd['_link_text_default'] = $rcd['_link_text_default'] ?? $rcd['title'] ?? $rcd['_link_text_default'] ?? $rcd['slug'];
	$rcd['_url_canonical'] = '?set=post_wiki&slug=' .U($rcd['slug']);
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

function array_subscripts(array $data, ...$keys) : array
{
	$ret = [];
	foreach ($keys as $k)
		if (array_key_exists($k, $data))
			$ret[$k] = $data[$k];
		else
			throw new Exception(sprintf('missing key: "%s"', $k));
	return $ret;
}

	# perform actions specific to this server & this configuration
	# to flush the whole HTTP pipeline
function http_flush()
{
	ob_flush();
	flush();
}

define('EX_DIR_PREFIX', realpath('../libexec'));

function in_dirP(string $pathname, string $in_dir) : bool
{
	$pathname = realpath($pathname);
		# the trailing suffix is for pattern matching - to ensure we're not *extending* the name of the dir
	$prefix = realpath($in_dir) .'/';
	return (strncmp($pathname, $prefix, strlen($prefix))) === 0;
}

function ex(string $pathname, array $data = [])
{
	if (!in_dirP($pathname, '../libexec'))
		throw new Exception(sprintf('not in the designated directory'));

	unset($data['data']);
	extract($data);

	require func_get_arg(0);
}

if (!function_exists('header_response_code')) {
	function header_response_code(int $code = null) : int
	{
		static $previous_code = 200;

		switch ($code) {
		case null: break;
		case 100: $text = 'Continue'; break;
		case 101: $text = 'Switching Protocols'; break;
		case 200: $text = 'OK'; break;
		case 201: $text = 'Created'; break;
		case 202: $text = 'Accepted'; break;
		case 203: $text = 'Non-Authoritative Information'; break;
		case 204: $text = 'No Content'; break;
		case 205: $text = 'Reset Content'; break;
		case 206: $text = 'Partial Content'; break;
		case 300: $text = 'Multiple Choices'; break;
		case 301: $text = 'Moved Permanently'; break;
		case 302: $text = 'Moved Temporarily'; break;
		case 303: $text = 'See Other'; break;
		case 304: $text = 'Not Modified'; break;
		case 305: $text = 'Use Proxy'; break;
		case 400: $text = 'Bad Request'; break;
		case 401: $text = 'Unauthorized'; break;
		case 402: $text = 'Payment Required'; break;
		case 403: $text = 'Forbidden'; break;
		case 404: $text = 'Not Found'; break;
		case 405: $text = 'Method Not Allowed'; break;
		case 406: $text = 'Not Acceptable'; break;
		case 407: $text = 'Proxy Authentication Required'; break;
		case 408: $text = 'Request Time-out'; break;
		case 409: $text = 'Conflict'; break;
		case 410: $text = 'Gone'; break;
		case 411: $text = 'Length Required'; break;
		case 412: $text = 'Precondition Failed'; break;
		case 413: $text = 'Request Entity Too Large'; break;
		case 414: $text = 'Request-URI Too Large'; break;
		case 415: $text = 'Unsupported Media Type'; break;
		case 500: $text = 'Internal Server Error'; break;
		case 501: $text = 'Not Implemented'; break;
		case 502: $text = 'Bad Gateway'; break;
		case 503: $text = 'Service Unavailable'; break;
		case 504: $text = 'Gateway Time-out'; break;
		case 505: $text = 'HTTP Version not supported'; break;
		default:
			throw new Exception(sprintf('unsupported code "%s"', $code)); }

		
		header(sprintf('%s %d %s',
			$_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0',
			$code,
			$text ));

		return $previous_code;
	} }

function tlb_pathname(string $tpl_selector) : string
{
	return (__DIR__ .'/' .$tpl_selector);
}

function tpl(string $tpl_selector, array $data)
{
	if (array_key_exists('data', $data))
		throw new Exception('unsupported index: data');
	if (array_key_exists('tpl_selector', $data))
		throw new Exception('unsupported index: tpl_selector');
	extract($data);

	return require tlb_pathname($tpl_selector);
}

require 'lib_wiki.php';
require 'lib_wiki_sync.php';
require 'lib_tlb.php';
require 'lib_update.php';
require 'lib_diff.php';
