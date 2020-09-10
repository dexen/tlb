<?php

$Diff = new dexen\diff\Diff();

$lrcd = $DB->queryFetch('SELECT COALESCE(body, \'\') AS body FROM (SELECT NULL) AS _x LEFT JOIN _wiki_versioned ON slug = ? AND _is_latest = 1', [ $connection_slug ]);
$rrcd = $DB->queryFetch('SELECT COALESCE(body, \'\') AS body  FROM (SELECT NULL) AS _x LEFT JOIN _wiki_versioned_remote ON connection = ? AND slug = ? AND _is_latest = 1', [ $connection, $connection_slug ]);

echo '<pre>';
foreach ($Diff->fileA('local', $lrcd['body'])->fileB('remote', $rrcd['body'])->getDiff() as $str)
	echo H($str);
echo '</pre>';
