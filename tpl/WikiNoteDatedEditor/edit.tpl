<?php

$dH = function(array $rcd) : string
{
	if ($rcd['_day_of_week'] === null)
		return 'unknown';
	switch ($rcd['_day_of_week']) {
	case 0:
		return 'Sunday';
	case 1:
		return 'Monday';
	case 2:
		return 'Tuesday';
	case 3:
		return 'Wednesday';
	case 4:
		return 'Thursday';
	case 5:
		return 'Friday';
	case 6:
		return 'Saturday';
	default:
		throw new Exception(sprintf('unsupported day of week: "%s"', $rcd['_day_of_week'])); }
};

if ($nrcd === null) {
	$nrcd = $ndTodayRcd;
	$b = '+Add'; }
else
	$b = 'Save';

if ($nrcd['date'] === $ndTodayRcd['date'])
	$h = 'Today';
else
	$h = $date;
echo '<h2>' .H($h) .', ' .$dH($nrcd) .'</h2>';

echo '<form method="post" action="?set=' .H($set) .'&amp;slug=' .H($slug) .'&amp;date=' .HU($nrcd['date']) .'&amp;service=' .HU($service) .'&amp;form=edit">';

	echo '<p style="text-align: right;"><label>date override: <input size="11" name="data[date]" value="' .H($nrcd['date']) .'"/ ></label></p>';

	echo '<input type="hidden" name="original[body]" value="' .H($nrcd['body']) .'"/>';
	echo '<input type="hidden" name="meta[selectionStart]" value=""/>';
	echo '<input type="hidden" name="meta[selectionEnd]" value=""/>';
	echo '<textarea name="data[body]" style="width: 100%" rows="12">' .H($nrcd['body']) .'</textarea>';
	echo '<button id="qvv" type="submit" name="action" value="save-see" class="strut-12">' .H($b) .'</button>';
echo '</form>';
js_event_register_ctrl_click_2_click_id('e', 'qvv');
