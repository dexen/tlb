<?php

require '../init.php';

$slug = $_GET['slug']??null;

$DB = db_pdo();

$rcd = $DB->queryFetch('SELECT * FROM post_wiki WHERE _url_slug = ?', [ $_GET['slug']??null ]);
#die(($_GET['slug']??null).'-xatt');
echo $rcd['body'];
