<?php

require '../init.php';

$DB = db_pdo();

td($DB->queryFetchAll('SELECT * FROM post_wiki'));

echo 'ALL DONE.';
