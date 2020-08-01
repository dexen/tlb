<?php

$id = $_GET['id']??null;
$size = $_GET['size']??null;
if (empty($id) || empty($size) || ($size > 4096) || ($size <= 0)) {
	header('HTTP/1.1 400 Bad Request');
	die('bad request'); }

header('Content-Type: image/png');
header('Content-Disposition: inline; filename="visual-hash-' .rawurlencode($id) .'.png"');
header('Cache-Control: public, max-age=365000000');

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

$A = [ 0, 0, 0, 0xff ];
$B = [ 0xff, 0xff, 0xff, 0xff ];

$n = 0;
$A[0] = ord($id[$n++]);
$A[1] = ord($id[$n++]);
$A[2] = ord($id[$n++]);
$B[0] = ord($id[$n++]);
$B[1] = ord($id[$n++]);
$B[2] = ord($id[$n++]);

define('_', $A);
define('B', $B);

$pattern = [
	[ _, _, _, _, B, B, B, B ],
	[ _, B, B, B, _, _, _, B ],
	[ _, B, B, B, _, _, _, B ],
	[ _, B, B, B, _, _, _, B ],
	[ _, B, B, B, _, _, _, B ],
	[ _, B, B, B, _, _, _, B ],
	[ _, B, B, B, _, _, _, B ],
	[ _, _, _, _, B, B, B, B ], ];

$gengen = function(int $size, $A, $B) use($pattern) : array
{
	if (($size%8)) {
		header('HTTP/1.1 400 Bad Request');
		die('bad request - size must be a multiple of 8'); }

	$factor = $size / 8;

		# in case we screw up
	$blank_px = [ 0, 0, 0xFE, 0x80 ];
	$blank_sl = array_fill(0, $size, $blank_px);
	$ret = array_fill(0, $size, $blank_sl);

	for ($y = 0; $y < $size; ++$y)
		for ($x = 0; $x < $size; ++$x)
			$ret[$y][$x] = $pattern[$y/$factor][$x/$factor];

	return $ret;;
};

$SLOPAA = $gengen($size, $A, $B);

$compress = fn(string $data) => gzdeflate($data, $level = 9, ZLIB_ENCODING_DEFLATE);
$filter_type_none = "\x00";
$filter = fn(array /* of strings */ $SLA) => $filter_type_none .implode($filter_type_none, $SLA);
	# value pack to pixel string
$vp2ps = fn(array /* of RGBA */ $a) => pack('CCCC', ...$a);
$slvp2ps = fn(array $a) => implode(array_map($vp2ps, $a));
$scanline_serialize = fn(array /* of arrays */ $SLOPAA) => array_map($slvp2ps, $SLOPAA);

echo $png_header();
echo $png_IHDR($size, $size, $bit_dept = 8, $true_colour_with_alpha);
echo $png_IDAT(
	$compress(
		$filter(
			$scanline_serialize($SLOPAA) ) ) );
echo $png_IEND();
