<?php

echo '<h3>WikiSyncService <a href="?set=post_wiki&amp;slug=WikiSyncService">?</a></h3>';

wiki_store_verison_history($connection, wiki_fetch_version_history($connection));

$newA = $DB->queryFetchAll('
	SELECT _r.slug, _r.mtime, datetime(_r.mtime, \'unixepoch\', \'localtime\') AS mtime_localtime
	FROM _wiki_versioned_remote AS _r
	LEFT JOIN wiki AS _l USING(slug)
	WHERE _r.connection = ?
		AND _l.slug IS NULL
		AND _r._is_latest = 1
		AND _r.body IS NOT NULL
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
	echo '</tr>'; }
echo '</tbody>';
echo '</table>';

$diffA = $DB->queryFetchAll('
	SELECT _r.slug,
		_r.mtime AS mtime_r, datetime(_r.mtime, \'unixepoch\', \'localtime\') AS mtime_r_localtime,
		_l.mtime AS mtime_l, datetime(_l.mtime, \'unixepoch\', \'localtime\') AS mtime_l_localtime
	FROM _wiki_versioned_remote AS _r
	JOIN wiki AS _l USING(slug)
	WHERE _r.connection = ?
		AND _r._body_sha1 != _l._body_sha1
	ORDER BY _r.mtime DESC',
	[ $connection ] );

echo '<table>';
echo '<caption><h4>Differing</h4></caption>';
echo '<thead>';
	echo '<th></th><th>local</th><th>remote</th>';
echo '</thead>';

echo '<tbody>';
foreach ($diffA as $rcd) {
	echo '<tr>';
		echo '<th>' .H($rcd['slug']) .'</th>';
		echo '<td>', H($rcd['mtime_r_localtime']), '</td>';
		echo '<td>', H($rcd['mtime_l_localtime']), '</td>';
	echo '</tr>'; }
echo '</tbody>';
echo '</table>';
