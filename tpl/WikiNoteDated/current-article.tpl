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

echo '<h2>Notes</h2>';

$hasToday = array_reduce($ndA, fn($carry, $rcd) => ($rcd['date'] === $ndTodayRcd['date']) ? true : $carry);

if (!$hasToday) {
	echo '<section>';
		echo '<h3>Today, ' .$dH($ndTodayRcd) .'<button type="button" class="strut-3" style="float: right">+Add</button></h3>';
		echo '<div style="clear: both"></div>';
	echo '</section>'; }

foreach ($ndA as $nrcd) {
	echo '<section>';
		if ($nrcd['date'] === $ndTodayRcd['date'])
			$v = 'Today';
		else
			$v = $nrcd['date'];
		echo '<h3>' .H($v) .', ' .$dH($nrcd) .' <button type="button" class="strut-3" style="float: right">Edit</button></h3>';
		echo '<p>' .H($nrcd['body']) .'</p>';
	echo '</section>'; }
