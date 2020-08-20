<?php

$Diff = new dexen\diff\Diff();

$lrcd = $DB->queryFetch('SELECT COALESCE(body, \'\') AS body FROM _wiki_versioned WHERE slug = ? AND _is_latest = 1', [ $slug ]);
$rrcd = $DB->queryFetch('SELECT COALESCE(body, \'\') AS body  FROM _wiki_versioned_remote WHERE connection = ? AND slug = ? AND _is_latest = 1', [ $connection, $slug ]);

echo '<pre>';
foreach ($Diff->fileA('local', $lrcd['body'])->fileB('remote', $rrcd['body'])->getDiff() as $str)
	echo H($str);
echo '</pre>';
