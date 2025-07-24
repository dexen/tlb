<?php

function wiki_slugs_from_local() : array
{
	return db_pdo()->queryFetchAll('
		SELECT _url_slug AS slug, _mtime, _body_sha1
		FROM post_wiki',
		[],
		PDO::FETCH_ASSOC );
}

function wiki_sync_data_from_local()
{
	return db_pdo()->queryFetchAll('
		SELECT _url_slug AS slug, body, _mtime
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

function wiki_fetch_version_history(string $connection)
{
	$curl = tlb_connection_url($connection);
	$url = $curl .'/wiki-version-history.php';
	return json_decode(tlb_download_connection($url), $assoc = true);
}

function wiki_store_verison_history(string $connection, ?array $data = null)
{
	if (empty($data))
		return;

	$DB = db_pdo();

	$DB->beginTransaction();
		$DB->execParams('DELETE FROM _wiki_versioned_remote WHERE connection = ?', [ $connection ]);

		$St = $DB->prepare('INSERT INTO _wiki_versioned_remote (connection, slug, mtime, _is_latest, body, _body_sha1) VALUES (?, ?, ?, ?, ?, ?)');

		foreach ($data as $rcd)
			$St->execute([ $connection, $rcd['slug'], $rcd['mtime'], $rcd['_is_latest'], $rcd['body'], sha1($rcd['body']) ]);

	$DB->commit();
}
