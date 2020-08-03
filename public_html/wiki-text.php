<?php

require '../init.php';

$slug = $_GET['slug']??null;

$DB = db_pdo();

$rcd = $DB->queryFetch('SELECT * FROM post_wiki WHERE _url_slug = ?', [ $_GET['slug']??null ]);
if ($rcd)
	echo $rcd['body'];
