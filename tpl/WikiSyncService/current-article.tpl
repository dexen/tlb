<?php

echo '<h3>WikiSyncService <a href="?set=post_wiki&amp;slug=WikiSyncService">?</a></h3>';

$local_a = wiki_slugs_from_local();
$remote_a = wiki_slugs_from_connection($connection);

$a = [];
foreach ($local_a as $rcd)
	$a[$rcd['slug']]['local']['_mtime'] = $rcd['_mtime'];
foreach ($remote_a as $rcd)
	$a[$rcd['slug']]['remote']['_mtime'] = $rcd['_mtime'];

ksort($a);

echo '<table>';
echo '<thead>';
	echo '<th></th><th>local</th><th>remote</th>';
echo '</thead>';

echo '<tbody>';
foreach ($a as $slug => $rcd) {
	echo '<tr>';
		echo '<th>', H($slug), '</th>';
		echo '<td>', H($rcd['local']['_mtime']??'--'), '</td>';
		echo '<td>', H($rcd['remote']['_mtime']??'--'), '</td>';
	echo '</tr>'; }
echo '</tbody>';
echo '</table>';
