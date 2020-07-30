<?php

require '../init.php';

$DB = db_pdo();

if (($_GET['set']??null) === 'post_wiki') {
	if (($_GET['form']??null) === 'maintenance') {
		if (($_POST['action']??null) === 'rebuild-slug-reverse-index')
			wiki_maintenance_rebuild_slug_reverse_index();
			echo '<a href="?">ALL DONE.</a>'; die(); } }

if (array_key_exists('slug', $_GET)) {
	$rcd = $DB->queryFetch('SELECT * FROM post_wiki WHERE _url_slug = ?', [ $_GET['slug']??null ]);

	if (($_GET['form']??null) === 'edit') {
		if ($rcd)
			$slug = $rcd['_url_slug'];
		else
			$slug = $_GET['slug'] ?? null;

		if (($_POST['action']??null) === 'save-edit') {
			$DB->beginTransaction();
				$DB->execParams('
					DELETE FROM _wiki_slug_use
					WHERE post_id IN (SELECT post_id FROM post_wiki WHERE _url_slug = ?)', [ $slug ]);
				if (empty($rcd))
					$DB->execParams('INSERT INTO post_wiki (body, _url_slug, uuid) VALUES (?, ?, ?)',
						[ $_POST['body'], $slug, Uuid::generateUuidV4() ] );
				else if (empty($_POST['body']))
					$DB->execParams('
						DELETE FROM post_wiki WHERE _url_slug = ? ',
						[ $slug ] );
				else
					$DB->execParams('UPDATE post_wiki SET body = ? WHERE _url_slug = ?',
						[ $_POST['body'], $slug ]);
				$rcd = $DB->queryFetch('SELECT post_id, body FROM post_wiki WHERE _url_slug = ?', [ $slug ]);
				if ($rcd) {
					$St = $DB->prepare('INSERT INTO _wiki_slug_use (post_id, _url_slug) VALUES (?, ?)');
					foreach (wiki_post_to_linked_slugs($rcd) as $v)
						$St->execute([ $rcd['post_id'], $v ]); }
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

if (($_GET['form']??null) === 'edit') {
	if ($rcd)
		$slug = $rcd['_url_slug'];
	else
		$slug = $_GET['slug'] ?? null;

	echo '<form method="post" action="?set=post_wiki&amp;slug=', HU($slug) ,'&amp;form=edit" enctype="multipart/form-data">';

		echo '<fieldset>';
		echo '<legend>post_wiki edit</legend>';

		$rows = max(count(explode("\n", $rcd['body']??null))+3, 20);
		echo '<label>body:<br><textarea name="body" style="width: 100%" rows="', H($rows), '">', H($rcd['body']??null), '</textarea></label>';
		echo '<p><button type="submit" name="action" value="save-edit" style="width: 100%; min-height: 8ex">Save <var>' .H($slug) .'</var></button></p>';
		echo '</fieldset>';
	echo '</form>'; }

if (array_key_exists('slug', $_GET)) {
	if ($rcd) {
		echo wiki_post_title_to_htmlH($rcd);
		echo wiki_post_body_to_htmlH($rcd);
		echo '<hr>';
		echo wiki_post_edit_formH($rcd); }
	else {
		echo '<h1>Wiki entry not found</h1>';
		echo '<hr>';
		echo '<p><em>The wiki entry for ' .wiki_slug_to_linkH($_GET['slug']) . ' has not been found. Create?</em></p>';
		echo wiki_post_edit_formH([ '_url_slug' => $_GET['slug'] ]); } }

	$riA = $DB->queryFetchAll('
		SELECT p.*
		FROM post_wiki AS p
		JOIN _wiki_slug_use AS u ON p.post_id = u.post_id
		WHERE u._url_slug= ?', [ $_GET['slug']??null ]);

	echo '<h1>Wiki services</h1>';
	echo '<h2>Reverse index</h2>';
		echo '<ul>';
			foreach (posts_process($riA) as $rcd) {
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
			}
		echo '</ul>';

if (!array_key_exists('slug', $_GET)) {
	if (true) {
		$a = posts_process($DB->queryFetchAll('SELECT * FROM post_wiki'));
		echo '<h2>Post index</h2>';
		if (empty($a))
			echo '<em>no wiki posts</em>';
		echo '<ul>';
			foreach ($a as $rcd) {
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
			}
		echo '</ul>'; }

		$mpA = $DB->queryFetchAll('
			SELECT u._url_slug
			FROM _wiki_slug_use AS u
			LEFT JOIN post_wiki AS p ON u._url_slug = p._url_slug
			WHERE p._url_slug IS NULL' );

		echo '<h2>Missing articles</h2>';
		echo '<ul>';
			foreach (posts_process($mpA) as $rcd)
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
		echo '</ul>';

		$mpA = $DB->queryFetchAll('
			SELECT p._url_slug
			FROM post_wiki AS p
			LEFT JOIN _wiki_slug_use AS u ON p._url_slug = u._url_slug
			WHERE u._url_slug IS NULL' );

		echo '<h2>Orphan pages</h2>';
		echo '<ul>';
			foreach (posts_process($mpA) as $rcd)
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
		echo '</ul>';

		echo '<h2>Maintenance tasks</h2>';
		echo wiki_maintenance_refresh_slug_reverse_index_formH(); }

echo '<p>ALL DONE.</p>';

echo '<footer>';
printf('<p><a href="?set=post_wiki">index</a> | <em>time: %.3f </em></p>', microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
