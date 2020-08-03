<?php

require '../init.php';

$slug = $_GET['slug']??null;

echo implode(',',
	array_column(
		$riA = db_pdo()->queryFetchAll('
			SELECT p.*
			FROM post_wiki AS p
			JOIN _wiki_slug_use AS u ON p.post_id = u.post_id
			WHERE u._url_slug= ?', [ $slug ]),
		'_url_slug' ) );
