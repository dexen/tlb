<?php

$Diff = new dexen\diff\Diff();

$lrcd = $DB->queryFetch('SELECT COALESCE(body, \'\') AS body FROM (SELECT NULL) AS _x LEFT JOIN wiki ON slug = ?', [ $slug ]);
if ($connection === tlb_address())
	$rrcd = $DB->queryFetch('SELECT COALESCE(body, \'\') AS body FROM (SELECT NULL) AS _x LEFT JOIN wiki ON slug = ?', [ $connection_slug ]);
else
	$rrcd = $DB->queryFetch('SELECT COALESCE(body, \'\') AS body  FROM (SELECT NULL) AS _x LEFT JOIN _wiki_versioned_remote ON connection = ? AND slug = ? AND _is_latest = 1', [ $connection, $connection_slug ]);

echo '<pre>';
foreach ($Diff->fileA('local', $lrcd['body'])->fileB('remote', $rrcd['body'])->getDiff() as $str)
	echo H($str);
echo '</pre>';
