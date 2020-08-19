<?php

require '../init.php';

$slug = $_GET['slug']??null;

echo implode(',',
	array_column(
		$riA = db_pdo()->queryFetchAll('
			SELECT from_slug AS slug
			FROM _wiki_slug_use AS u
			WHERE u.to_slug = ?', [ $slug ]),
		'slug' ) );
