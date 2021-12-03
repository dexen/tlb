<?php

$id = $_GET['id']??null;
$size = $_GET['size']??null;
if (empty($id) || empty($size) || ($size > 4096) || ($size <= 0)) {
	header('HTTP/1.1 400 Bad Request');
	die('bad request'); }

$png_header = function() : string { return "\x89PNG\x0d\x0a\x1a\x0a"; };
$png_chunk = function(string $type, string $data) { return pack('N', strlen($data)) .$type .$data .pack('N', crc32($type .$data)); };
$png_IHDR = function(int $width, int $height, int $bit_depth, int $color_type) use($png_chunk) {
	return
	$png_chunk('IHDR', pack(
		'NNCCCCC',
		$width, $height,
		$bit_depth, $color_type,
		$compressin_method = 0, $filter_method = 0, $transmission_order = $no_interlace = 0 ) ); };
$png_IDAT = function(string $data) use($png_chunk) { return $png_chunk('IDAT', $data); };
$png_IEND = function() use($png_chunk) { return $png_chunk('IEND', ''); };

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

$compress = function(string $data) { return gzdeflate($data, $level = 9, ZLIB_ENCODING_DEFLATE); };
$filter_type_none = "\x00";
$filter = function(array /* of strings */ $SLA) use($filter_type_none) { return $filter_type_none .implode($filter_type_none, $SLA); };
	# value pack to pixel string
$vp2ps = function(array /* of RGBA */ $a) { return pack('CCCC', ...$a); };
$slvp2ps = function(array $a) use($vp2ps) { return implode(array_map($vp2ps, $a)); };
$scanline_serialize = function(array /* of arrays */ $SLOPAA) use($slvp2ps) { return array_map($slvp2ps, $SLOPAA); };

header('Content-Type: image/png');
header('Content-Disposition: inline; filename="visual-hash-' .rawurlencode($id) .'.png"');
header('Cache-Control: public, max-age=365000000, immutable');

echo $png_header();
echo $png_IHDR($size, $size, $bit_dept = 8, $true_colour_with_alpha);
echo $png_IDAT(
	$compress(
		$filter(
			$scanline_serialize($SLOPAA) ) ) );
echo $png_IEND();
