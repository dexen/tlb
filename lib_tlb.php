<?php

function tlb_address_hash_salt() : string
{
		# FIXME: generate a hash or nonce based on TlbConfig?
		# or perhaps on something related to tor?
	return -1;
}

function tlb_address() : string
{
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

function tlb_address_id()
{
	return sha1(tlb_address() ."\x00" .tlb_address_hash_salt());
}
