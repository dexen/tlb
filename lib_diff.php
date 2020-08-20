<?php


namespace dexen\Diff;

use Generator;

class Diff
{
	protected $file_a;
	protected $file_b;

	protected $records_a;
	protected $records_b;

	protected $mtime_a;
	protected $mtime_b;

	protected $packets;

	protected $Packetizer;

	function __construct(/* future options */)
	{
		$this->Packetizer = new PacketizerRecursive();
	}

	protected
	function asStream(string $pathname, string $content = null)
	{
		if ($content !== null) {
			$h = fopen('php://memory','r+');
			fwrite($h, $content);
			rewind($h);
			return $h; }
		else if ($pathname === '-')
			return STDIN;
		else
			return fopen($pathname, 'r');
	}

	protected
	function asRecords(string $file, string $content = null) : array
	{
		$ret = [];
		$h = $this->asStream($file, $content);

		for ($n = 1; ($str = fgets($h)) !== false; ++$n)
			$ret[] = [ $n, $str ];

		return $ret;
	}

	protected
	function debugRecords(string $str, array $records)
	{
		$a = [];
		foreach ($records as $rcd)
			$a[] = substr($str, $rcd[1], $rcd[2]);
		td($a);
	}

	protected
	function asMtime(string $pathname) : int { return ($pathname === '-') ? time() : stat($pathname)['mtime']; }

	function fileA(string $pathname, string $content = null) : self
	{
		$this->file_a = $pathname;
		$this->records_a = $this->asRecords($this->file_a, $content);
		if ($content === null)
			$this->mtime_a = $this->asMtime($pathname);
		return $this;
	}

	function fileB(string $pathname, string $content = null) : self
	{
		$this->file_b = $pathname;
		$this->records_b = $this->asRecords($this->file_b, $content);
		if ($content === null)
			$this->mtime_b = $this->asMtime($pathname);
		return $this;
	}

	protected
	function computeDiffPackets()
	{
		$this->packets = $this->Packetizer->linesA($this->records_a)->linesB($this->records_b)->getDiffPackets();
	}

	protected
	function serializeDiffPackets() : Generator
	{
		$na = 0;
		$nb = 0;

		foreach ($this->packets as $packet) {
			$rcd_a = [ -1, '' ];
			$rcd_b = [ -1, '' ];

			[ $records_a, $records_b ] = [ $packet['aa'], $packet['bb'] ];

			if (empty($packet['is_blank']))
				yield sprintf("@@ -%d,%d +%d,%d @@\n",
					$na+!empty($records_a), count($records_a),
					$nb+!empty($records_b), count($records_b) );

			foreach ($records_a as $rcd_a)
				if ($rcd_a[1] !== null)
					yield sprintf("-%s", $rcd_a[1]);
			$na += count($records_a);

			if (strlen($rcd_a[1]))
				if ($rcd_a[1][strlen($rcd_a[1])-1] !== "\n")
					yield "\n\\ No newline at the end of file\n";

			foreach ($records_b as $rcd_b)
				if ($rcd_b[1] !== null)
					yield sprintf("+%s", $rcd_b[1]);
			$nb += count($records_b);

			if (strlen($rcd_b[1]))
				if ($rcd_b[1][strlen($rcd_b[1])-1] !== "\n")
					yield "\n\\ No newline at the end of file\n"; }
	}

	protected
	function serializeFileHead() : Generator
	{
		yield sprintf("--- %s\t%s\n", $this->file_a, date('Y-m-d H:i:s O', $this->mtime_a));
		yield sprintf("+++ %s\t%s\n", $this->file_b, date('Y-m-d H:i:s O', $this->mtime_b));
	}

	function getDiff() : Generator
	{
		$this->computeDiffPackets();
		yield from $this->serializeFileHead();
		yield from $this->serializeDiffPackets();
	}
}


namespace dexen\Diff;

interface Packetizer
{
	function linesA(array $records_a) : self;
	function linesB(array $records_b) : self;
	function getDiffPackets() : array;
}


namespace dexen\Diff;

class PacketizerRecursive implements Packetizer
{
	protected $lines_a;
	protected $lines_b;

	function linesA(array $lines_a) : self
	{
		$this->lines_a = $lines_a;
		return $this;
	}

	function linesB(array $lines_b) : self
	{
		$this->lines_b = $lines_b;
		return $this;
	}

	protected
	function commonLineP(array $rcd_a, array $rcd_b) : bool
	{
		return $rcd_a[1] === $rcd_b[1];
	}

	protected
	function bestCommonSubsetAtAt(array $lines_a, array $lines_b, int $an, int $bn) : int
	{
		$match_length = 0;
		while (true) {
			if (!array_key_exists($an, $lines_a))
				return $match_length;
			if (!array_key_exists($bn, $lines_b))
				return $match_length;
			if ($lines_a[$an][1] === null)
				throw new \Exception('should not happen');
			if ($lines_b[$bn][1] === null)
				throw new \Exception('should not happen');

			if ($lines_a[$an] === $lines_b[$bn])
				++$match_length;
			else
				return $match_length;
			++$an;
			++$bn; }
	}

	protected
	function bestCommonSubsetAt(array $lines_a, array $lines_b, int $an) : array
	{
		$best_bn = 0;
		$best_len = 0;

		for ($bn = 0; array_key_exists($bn, $lines_b); ++$bn)
			if (($len = $this->bestCommonSubsetAtAt($lines_a, $lines_b, $an, $bn)) > $best_len)
				[ $best_bn, $best_len ] = [ $bn, $len ];

		return [ $best_bn, $best_len ];
	}

	protected
	function bestCommonSubset(array $lines_a, array $lines_b) : array
	{
		$best_an = 0;
		$best_bn = 0;
		$best_len = 0;

		for ($an = 0; array_key_exists($an, $lines_a); ++$an) {
			[ $bn, $len ] = $this->bestCommonSubsetAt($lines_a, $lines_b, $an);
			if ($len > $best_len)
				[ $best_an, $best_bn, $best_len ] = [ $an, $bn, $len ]; }
		return [ $best_an, $best_bn, $best_len ];
	}

	protected
	function asCommon(array $a) : array
	{
		return array_map(fn($a)=>[ $a[0], null, $a[1] ], $a);
	}

	protected
	function whiteout($lines_a, $lines_b, $an, $bn, $len) : \Generator
	{
		if ($len === 0) {
			yield [ 'aa' => $lines_a, 'bb' => $lines_b ];
			return; }

		if (empty($lines_a) || empty($lines_b)) {
			yield [ 'aa' => $lines_a, 'bb' => $lines_b ];
			return; }

		$pre_a = array_slice($lines_a, 0, $an);
		$pre_b = array_slice($lines_b, 0, $bn);

		$common_a = array_slice($lines_a, $an, $len);
		$common_b = array_slice($lines_b, $bn, $len);
		yield [ 'aa' => $this->asCommon($common_a), 'bb' => $this->asCommon($common_b), 'is_blank' => true ];

		if ($pre_a && $pre_b)
			yield from $this->whiteout($pre_a, $pre_b, ...$this->bestCommonSubset($pre_a, $pre_b));
		else
			yield [ 'aa' => $pre_a, 'bb' => $pre_b ];

		$post_a = array_slice($lines_a, $an+$len);
		$post_b = array_slice($lines_b, $bn+$len);

		if ($post_a && $post_b)
			yield from $this->whiteout($post_a, $post_b, ...$this->bestCommonSubset($post_a, $post_b));
		else
			yield [ 'aa' => $post_a, 'bb' => $post_b ];
	}

	function getDiffPackets() : array
	{
		$packets = [];

		[ $an, $bn, $len ] = $this->bestCommonSubset($this->lines_a, $this->lines_b);

		foreach ($this->whiteout($this->lines_a, $this->lines_b, $an, $bn, $len) as $packet)
			if ($packet['aa'] || $packet['bb'])
				$packets[] = $packet;

		return $packets;
	}
}
