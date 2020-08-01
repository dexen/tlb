<?php

$id = $_GET['id']??null;
$size = $_GET['size']??null;
if (empty($id) || empty($size) || ($size > 4096) || ($size<= 0)) {
	header('HTTP/1.1 400 Bad Request');
	die('bad request'); }

$size = (int)$size;
if ($size !== 8) {
	header('HTTP/1.1 400 Bad Request');
	die('bad request'); }

header('Content-Type: image/png');

$png_header = function() : string { return "\x89PNG\x0d\x0a\x1a\x0a"; };
$png_chunk = fn(string $type, string $data) => pack('N', strlen($data)) .$type .$data .pack('N', crc32($type .$data));

$png_IHDR = function(int $width, int $height, int $bit_depth, int $color_type) use($png_chunk) : string
{
	$data = pack(
		'NNCCCCC',
		$width, $height,
		$bit_depth, $color_type, $compressin_method = 0,
		$filter_method = 0, $transmission_order = $no_interlace = 0 );

	return $png_chunk('IHDR', $data);
};

$png_IEND = fn() => $png_chunk('IEND', '');
$png_IDAT = fn(string $data) => $png_chunk('IDAT', $data);

$true_colour = 2;
$true_colour_with_alpha = 6;

echo $png_header();
echo $png_IHDR($size, $size, $bit_dept = 8, $true_colour_with_alpha);

$A = [ 0, 0, 0, 0xff ];
$B = [ 0xff, 0xff, 0xff, 0xff ];

$n = 0;
$A[0] = ord($id[$n++]);
$A[1] = ord($id[$n++]);
$A[2] = ord($id[$n++]);
$B[0] = ord($id[$n++]);
$B[1] = ord($id[$n++]);
$B[2] = ord($id[$n++]);

$SLOPAA = [
	[ $A, $A, $A, $A, $B, $B, $B, $B ],
	[ $A, $B, $B, $B, $A, $A, $A, $B ],
	[ $A, $B, $B, $B, $A, $A, $A, $B ],
	[ $A, $B, $B, $B, $A, $A, $A, $B ],
	[ $A, $B, $B, $B, $A, $A, $A, $B ],
	[ $A, $B, $B, $B, $A, $A, $A, $B ],
	[ $A, $B, $B, $B, $A, $A, $A, $B ],
	[ $A, $A, $A, $A, $B, $B, $B, $B ],
];

$compress = fn(string $data) => gzdeflate($data, $level = 9, ZLIB_ENCODING_DEFLATE);

$filter_type_none = "\x00";
$filter = fn(array /* of strings */ $SLA) => $filter_type_none .implode($filter_type_none, $SLA);

	# value pack to pixel string
$vp2ps = fn(array /* of RGBA */ $a) => pack('CCCC', ...$a);
$slvp2ps = fn(array $a) => implode(array_map($vp2ps, $a));
$scanline_serialize = fn(array /* of arrays */ $SLOPAA) => array_map($slvp2ps, $SLOPAA);

echo $png_IDAT(
	$compress(
		$filter(
			$scanline_serialize($SLOPAA) ) ) );

echo $png_IEND();
