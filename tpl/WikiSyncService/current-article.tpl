<?php

echo '<h3>WikiSyncService <a href="?set=post_wiki&amp;slug=WikiSyncService">?</a></h3>';

wiki_store_verison_history($connection, wiki_fetch_version_history($connection));

$newA = $DB->queryFetchAll('
	SELECT _r.slug, _r.mtime, datetime(_r.mtime, \'unixepoch\', \'localtime\') AS mtime_localtime
	FROM _wiki_versioned_remote AS _r
	LEFT JOIN wiki AS _l USING(slug)
	WHERE _r.connection = ?
		AND _l.slug IS NULL
	ORDER BY _r.mtime DESC',
	[ $connection ] );


echo '<table>';
echo '<caption><h4>New</h4></caption>';
echo '<thead>';
	echo '<th></th><th>local</th><th>remote</th>';
echo '</thead>';

echo '<tbody>';
foreach ($newA as $rcd) {
	echo '<tr>';
		echo '<th>' .H($rcd['slug']) .'</th>';
		echo '<td>', H($rcd['mtime_localtime']), '</td>';
		echo '<td>--</td>';
		echo '<td><a href="#">copy...</a></td>';
	echo '</tr>'; }
echo '</tbody>';
echo '</table>';

$local_a = wiki_slugs_from_local();
$remote_a = wiki_slugs_from_connection($connection);

$a = [];
foreach ($local_a as $rcd)
	$a[$rcd['slug']]['local'] = $rcd;
foreach ($remote_a as $rcd)
	$a[$rcd['slug']]['remote'] = $rcd;

ksort($a);

echo '<table>';
echo '<caption><h4>Changed remote</h4></caption>';
echo '<thead>';
	echo '<th></th><th>local</th><th>remote</th>';
echo '</thead>';

echo '<tbody>';
foreach ($changedA as $rcd) {
	echo '<tr>';
		echo '<th>' .H($slug) .'</th>';
		echo '<td title="' .H($rcd['local']['_body_sha1']??null) .'">', H($rcd['local']['_mtime']??'--'), '</td>';
		echo '<td title="' .H($rcd['remote']['_body_sha1']??null) .'">', H($rcd['remote']['_mtime']??'--'), '</td>';
	echo '</tr>'; }
echo '</tbody>';
echo '</table>';
