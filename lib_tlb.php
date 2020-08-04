<?php

function tlb_address_hash_salt() : string
{
		# FIXME: generate a hash or nonce based on TlbConfig?
		# or perhaps on something related to tor?
	return -1;
}

function tlb_address(string $address = null) : string
{
	if ($address !== null)
		return $address;
	$protocol = $_SERVER['SERVER_PROTOCOL']??null;
	if ($protocol === null)
		throw new Exception('unknown protocol');
	if ($protocol === 'HTTP/1.0')
		$schema = 'http';
	else if ($protocol === 'HTTP/1.1')
		$schema = 'http';
# not sure what it actually shows
#	else if ($protocol === 'HTTP/2.0')
#		$schema = 'http';
	else
		throw new Exception('unsupported protocol');

	if ($schema === 'http')
		if (($_SERVER['HTTPS']??null) === 'on')
			$schema = 'https';

	$host = $_SERVER['HTTP_HOST'] ?? null;
	if ($host === null)
		throw new Exception('unknown hostname');

		# in this case we don't mind the port number
	if (preg_match('/[.]onion$/', $host))
		return $schema .'//' .$host;
	$port = $_SERVER['SERVER_PORT']??null;
	if ($port === null)
		throw new Exception('unknown port');
		return $schema .'//' .$host .':' .$port;
}

function tlb_address_id(string $address = null)
{
	return sha1(tlb_address($address) ."\x00" .tlb_address_hash_salt($address));
}

function tlb_config(string $key, string $default = null) : string
{
	$CDB = config_db_pdo();
	$rcd = $CDB->queryFetch('SELECT value FROM config WHERE key = ?', [ $key ]);
	if ($rcd === null)
		$v = $default;
	else
		$v = array_shift($rcd);

	if ($v === null)
		throw new Exception(sprintf('unsupported config: "%s"', $key));
	return $v;
}

function wiki_config_save(string $key, string $value)
{
	$CDB = config_db_pdo();
	$rcd = $CDB->execParams('UPDATE config SET value = ? WHERE key = ?', [ $value, $key ]);
}

function tlb_connection_records() : array /* of arrays */
{
	return
		array_map(fn($str) => explode(' ', $str),
			array_filter(
				explode("\n", tlb_config('federation.connections')),
				fn($str) => ($str !== '') && ($str[0] !== '#') ) );
}

function tlb_connections() : array
{
	return
		array_map('current',
			tlb_connection_records() );
}

function tlb_connection_url(string $key) : string
{
	return
			array_one(
				array_map(fn($rcd) => isset($rcd[1]) ? $rcd[1] : $rcd[0],
					array_filter(
						tlb_connection_records(),
						fn($rcd) => (($rcd[0]??null) === $key) || (isset($rcd[1]) && ($rcd[1] === $key)) ) ) );
}

function tlb_download_connection($url) : string
{
	$a = parse_url($url);
	$h = curl_init($url);
	if ($use_tor_proxy = preg_match('/[.]onion$/', $a['host']??null)) {
		curl_setopt($h, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
		curl_setopt($h, CURLOPT_PROXY, 'localhost:9050'); }
	else {
		curl_setopt($h, CURLOPT_PROXYTYPE, null);
		curl_setopt($h, CURLOPT_PROXY, null); }
	curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($h, CURLOPT_TIMEOUT, 3);
	$v = curl_exec($h);
#tp(compact('url', 'v', 'use_tor_proxy'));
	return $v;
}
