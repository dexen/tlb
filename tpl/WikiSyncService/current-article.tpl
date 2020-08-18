<?php

echo '<h3>WikiSyncService <a href="?set=post_wiki&amp;slug=WikiSyncService">?</a></h3>';

$local_a = wiki_slugs_from_local();
$remote_a = wiki_slugs_from_connection($connection);

$a = [];
foreach ($local_a as $rcd)
	$a[$rcd['slug']]['local'] = $rcd;
foreach ($remote_a as $rcd)
	$a[$rcd['slug']]['remote'] = $rcd;

ksort($a);


echo '<table>';
echo '<caption><h4>Changes</h4></caption>';
echo '<thead>';
	echo '<th></th><th>local</th><th>remote</th>';
echo '</thead>';

echo '<tbody>';
foreach ($a as $slug => $rcd) {
	if (($rcd['local']['_body_sha1']??null) === ($rcd['remote']['_body_sha1']??null))
		continue;
	echo '<tr>';
		echo '<th>', H($slug), '</th>';
		echo '<td title="' .H($rcd['local']['_body_sha1']??null) .'">', H($rcd['local']['_mtime']??'--'), '</td>';
		echo '<td title="' .H($rcd['remote']['_body_sha1']??null) .'">', H($rcd['remote']['_mtime']??'--'), '</td>';
	echo '</tr>'; }
echo '</tbody>';
echo '</table>';

echo '<h4>Same</h4>';

echo '<table>';
echo '<caption><h4>Same</h4></caption>';
echo '<thead>';
	echo '<th></th><th>local</th><th>remote</th>';
echo '</thead>';

echo '<tbody>';
foreach ($a as $slug => $rcd) {
	if (($rcd['local']['_body_sha1']??null) !== ($rcd['remote']['_body_sha1']??null))
		continue;
	echo '<tr>';
		echo '<th>', H($slug), '</th>';
		echo '<td title="' .H($rcd['local']['_body_sha1']??null) .'">', H($rcd['local']['_mtime']??'--'), '</td>';
		echo '<td title="' .H($rcd['remote']['_body_sha1']??null) .'">', H($rcd['remote']['_mtime']??'--'), '</td>';
	echo '</tr>'; }
echo '</tbody>';
echo '</table>';