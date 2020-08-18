<?php

function wiki_slugs_from_local() : array
{
	return db_pdo()->queryFetchAll('
		SELECT _url_slug AS slug, _mtime, _body_sha1
		FROM post_wiki',
		[],
		PDO::FETCH_ASSOC );
}

function wiki_slugs_from_connection(string $connection) : array
{
	$curl = tlb_connection_url($connection);
	$url = $curl .'/wiki-slug-index.php';
	return json_decode(tlb_download_connection($url), $assoc = true);
}

function wiki_recalc_all_body_sha1()
{
	$DB = db_pdo();
	while ($rcd = $DB->queryFetch('SELECT post_id, body FROM post_wiki WHERE _body_sha1 IS NULL LIMIT 1'))
			$DB->execParams('UPDATE post_wiki SET _body_sha1 = ? WHERE post_id = ?', [ sha1($rcd['body']), $rcd['post_id'] ]);
}
