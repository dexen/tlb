<?php

class Uuid
{
	static
	function generateUuidV4_old() : string
	{
		return implode('-',
			[
				bin2hex(random_bytes(4)),
				bin2hex(random_bytes(2)),
				bin2hex((random_bytes(2) & "\x0f\xff") | "\x40\x00"),
				bin2hex((random_bytes(2) & "\x3f\xff") | "\x80\x00"),
				bin2hex(random_bytes(6)) ] );
	}

	static
	function generateUuidV4() : string
	{
			# 16 bytes of UUID + 2 bytes for the four separators
			# the bin2hex() expands it to twice the size
		$hex = bin2hex(
					(random_bytes(16+2)
					& "\xff\xff\xff\xff\xff\xff\xff\x0f\xff\xf3\xff\xff\xff\xff\xff\xff\xff\xff")
					| "\0\0\0\0\0\0\0\x40\0\x08\0\0\0\0\0\0\0\0" );
		$hex[8] = $hex[13] = $hex[18] = $hex[23] = '-';
		return $hex;
	}

		# a quick-n-dirty check
		# for uuid-like string
		# does not check semantic rules
	protected static
	function expectUuidFormat(string $str) : bool
	{
		if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $str))
			return true;
		else
			throw new \Exception(sprintf('expected uuid, got "%s"', $str));
	}

	protected
	static function uuidToBytes(string $str) : string
	{
		static::expectUuidFormat($str);
		$str = str_replace('-', '', $str);
		return hex2bin($str);
	}

	static
	function generateUuidV5(string $namespaceUuid, string $name) : string
	{
		$namespaceBytes = static::uuidToBytes($namespaceUuid);
		$bytes = sha1($namespaceBytes .$name, $raw_output = true);

		$drain_bytes = function(int $cnt) use(&$bytes) : string
		{
			$ret = substr($bytes, 0, $cnt);
			$bytes = substr($bytes, $cnt);
			return $ret;
		};

		return implode('-',
			[
				bin2hex($drain_bytes(4)),
				bin2hex($drain_bytes(2)),
				bin2hex(($drain_bytes(2) & "\x0f\xff") | "\x50\x00"),
				bin2hex(($drain_bytes(2) & "\x3f\xff") | "\x80\x00"),
				bin2hex($drain_bytes(6)) ] );
	}
}
