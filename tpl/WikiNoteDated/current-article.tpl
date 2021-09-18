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

$hasToday = array_reduce($ndA, function($carry, $rcd) use($ndTodayRcd) { return ($rcd['date'] === $ndTodayRcd['date']) ? true : $carry; });

if (!$hasToday) {
	echo '<section>';
		echo '<form>';
			echo '<input type="hidden" name="set" value="' .H($set) .'"/>';
			echo '<input type="hidden" name="slug" value="' .H($slug) .'"/>';
			echo '<input type="hidden" name="date" value="' .H($ndTodayRcd['date']) .'"/>';
			echo '<input type="hidden" name="service" value="WikiNoteDatedEditor"/>';
			echo '<h3 title="' .H($ndTodayRcd['date']) .'">Today, ' .$dH($ndTodayRcd) .'<button type="submit" name="form" value="edit" class="strut-3" style="float: right">+Add</button></h3>';
			echo '<div style="clear: both"></div>';
		echo '</form>';
	echo '</section>'; }

foreach ($ndA as $nrcd) {
	echo '<section>';
		echo '<form>';
			echo '<input type="hidden" name="set" value="' .H($set) .'"/>';
			echo '<input type="hidden" name="slug" value="' .H($slug) .'"/>';
			echo '<input type="hidden" name="date" value="' .H($nrcd['date']) .'"/>';
			echo '<input type="hidden" name="service" value="WikiNoteDatedEditor"/>';
			if ($nrcd['date'] === $ndTodayRcd['date'])
				$v = 'Today';
			else
				$v = $nrcd['date'];
			echo '<h3 title="' .H($nrcd['date']) .'">' .H($v) .', ' .$dH($nrcd) .' <button type="submit" name="form" value="edit" class="strut-3" style="float: right">✎Edit</button></h3>';
			echo '<p>' .wiki_post_body_to_htmlH($nrcd['body']) .'</p>';
		echo '</form>';
	echo '</section>'; }
