<?php

require '../init.php';

$DB = db_pdo();

if (array_key_exists('slug', $_GET)) {
	$rcd = $DB->queryFetch('SELECT * FROM post_wiki WHERE _url_slug = ?', [ $_GET['slug']??null ]);

	if (($_GET['action']??null) === 'edit') {
		if ($rcd)
			$slug = $rcd['_url_slug'];
		else
			$slug = $_GET['slug'] ?? null;

		if (!empty($_POST)) {
			$DB->beginTransaction();
				if (empty($rcd))
					$DB->execParams('INSERT INTO post_wiki (body, _url_slug, uuid) VALUES (?, ?, ?)',
						[ $_POST['body'], $slug, Uuid::generateUuidV4() ] );
				else
					$DB->execParams('UPDATE post_wiki SET body = ? WHERE _url_slug = ?',
						[ $_POST['body'], $slug ]);
				$rcd = $DB->queryFetchOne('SELECT post_id, body FROM post_wiki WHERE _url_slug = ?', [ $slug ]);
				$DB->execParams('DELETE FROM _wiki_slug_use WHERE post_id = ?', [ $rcd['post_id'] ]);
				$St = $DB->prepare('INSERT INTO _wiki_slug_use (post_id, _url_slug) VALUES (?, ?)');
				foreach (wiki_post_to_linked_slugs($rcd) as $v)
					$St->execute([ $rcd['post_id'], $v ]);
			$DB->commit();
header('Location: ?set=post_wiki&slug=' .$slug);
die();
		}
}

	if ($rcd === null)
		header('HTTP/1.1 404'); }

echo '<!DOCTYPE html>';
echo '<html>';
echo '<head>';
echo '<meta charset="utf-8">';
echo '</head>';
echo '<body>';

if (($_GET['action']??null) === 'edit') {
	if ($rcd)
		$slug = $rcd['_url_slug'];
	else
		$slug = $_GET['slug'] ?? null;

	echo '<form method="post" action="?set=post_wiki&amp;slug=', HU($slug) ,'&amp;action=edit" enctype="multipart/form-data">';
		echo '<legend>post_wiki edit</legend>';

		echo '<p><button type="submit" style="width: 100%; min-height: 8ex">save</button></p>';
		echo '<p><button type="submit" style="width: 50%; min-height: 8ex">cancel</button></p>';
		$rows = max(count(explode("\n", $rcd['body']??null))+3, 20);
		echo '<label>body:<br><textarea name="body" style="width: 100%" rows="', H($rows), '">', H($rcd['body']??null), '</textarea></label>';
		echo '<p><button type="submit" style="width: 100%; min-height: 8ex">save</button></p>';
		echo '<p><button type="submit" style="width: 50%; min-height: 8ex">cancel</button></p>';
	echo '</form>'; }

if (array_key_exists('slug', $_GET)) {
	if ($rcd) {
		echo wiki_post_title_to_htmlH($rcd);
		echo wiki_post_body_to_htmlH($rcd);
		echo '<hr>';
		echo wiki_post_edit_formH($rcd); }
	else {
		echo '<h1>Wiki entry not found</h1>';
		echo '<p><em>The wiki entry for ' .wiki_slug_to_linkH($_GET['slug']) . ' has not been found. Create?</em></p>'; } }

if (array_key_exists('slug', $_GET)) {
	echo '<h2>Reverse index</h2>';

	$a = $DB->queryFetchAll('
		SELECT p._url_slug
		FROM post_wiki AS p
		JOIN _wiki_slug_use AS u ON p.post_id = u.post_id
		WHERE u._url_slug=?', [ $_GET['slug'] ]);
}

if (!array_key_exists('slug', $_GET)) {
	$a = posts_process($DB->queryFetchAll('SELECT * FROM post_wiki'));

	if (empty($a))
		echo '<em>no wiki posts</em>';
	else {
		echo '<h1>Wiki post index</h1>';
		echo '<ul>';
			foreach ($a as $rcd) {
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
			}
		echo '</ul>'; } }

echo '<p>ALL DONE.</p>';

echo '<footer>';
printf('<p><a href="?set=post_wiki">index</a> | <em>time: %.3f </em></p>', microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
