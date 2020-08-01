<?php

require '../init.php';

$DB = db_pdo();

$action = $_GET['action']??null;
$query = null;
$sA = [];
$rcd = null;
$slug = $_GET['slug']??null;

if (($_GET['set']??null) === 'post_wiki') {
	if (strncmp($action, 'search-', 7) === 0)
		$query = $_GET['q']??null;

	if ($action === 'search-slug')
		$sA = $DB->queryFetchAll('SELECT * FROM post_wiki WHERE _url_slug LIKE \'%\' || ? || \'%\' ', [ $query ] );
	else if ($action === 'search-content')
		$sA = $DB->queryFetchAll('SELECT * FROM post_wiki WHERE (_url_slug || \' \' || body)LIKE \'%\' || ? || \'%\' ', [ $query ] );

	if (count($sA) === 1)
		die(header('Location: ?set=post_wiki&slug=' .U($sA[0]['_url_slug'])));

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

				$DB->execParams('UPDATE post_wiki SET _mtime = strftime(\'%s\', \'now\') WHERE _url_slug = ?', [$slug]);

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
echo '<title>' .H(wiki_camel_to_spaced($rcd['_url_slug']??null)) .'</title>';
echo '</head>';
echo '<body style="max-width: 60ch">';

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

else if ($slug !== null) {
	if ($rcd) {
		echo '<h1>' .H(wiki_slug_to_title($rcd['_url_slug'])) .'</h1>';
		echo wiki_post_body_to_htmlH($rcd);
		echo '<hr>';
		if (($_GET['form']??null) !== 'edit')
			echo wiki_post_edit_formH($rcd); }
	else {
		echo '<h1>Wiki entry not found</h1>';
		echo '<hr>';
		echo '<p><em>The wiki entry for ' .wiki_slug_to_linkH($slug) . ' has not been found. Create?</em></p>';
		if (($_GET['form']??null) !== 'edit')
			echo wiki_post_edit_formH([ '_url_slug' => $_GET['slug'] ]); } }

	$riA = $DB->queryFetchAll('
		SELECT p.*
		FROM post_wiki AS p
		JOIN _wiki_slug_use AS u ON p.post_id = u.post_id
		WHERE u._url_slug= ?', [ $_GET['slug']??null ]);

	echo '<h1>Wiki services</h1>';
	echo '<h2>Reverse index <a class="help" href="?set=post_wiki&amp;slug=WikiReverseSlugIndex">?</a></h2>';
		echo '<ul>';
			foreach (posts_process($riA) as $rcd) {
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
			}
		echo '</ul>';

	echo '<h2>Search <a class="help" href="?set=post_wiki&amp;slug=WikiSearch">?</a></h2>';
	echo '<form>';
	echo '<input type="hidden" name="set" value="post_wiki"/><input type="hidden" name="slug" value="' .H($_GET['slug']??null) .'"/>';
	echo '<input name="q" placeholder="query" value="' .H($query) .'" ' .($query?'autofocus':null) .'/><button name="action" value="search-slug" type="submit">slug</button> | <button name="action" value="search-content" type="submit">content</button>';
	echo '</form>';
	if (($query !== null) && empty($sA))
		echo '<p><em>no matches</em></p>';
	else {
		echo '<ul>';
			foreach (posts_process($sA) as $rcd)
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
		echo '</ul>'; }

if (!array_key_exists('slug', $_GET)) {
	if (false) {
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

		echo '<h2>Missing pages <a class="help" href="?set=post_wiki&amp;slug=WikiMissingPagesIndex">?</a></h2>';
		echo '<ul>';
			foreach (posts_process($mpA) as $rcd)
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
		echo '</ul>';

		$mpA = $DB->queryFetchAll('
			SELECT p._url_slug
			FROM post_wiki AS p
			LEFT JOIN _wiki_slug_use AS u ON p._url_slug = u._url_slug
			WHERE u._url_slug IS NULL' );

		echo '<h2>Orphan pages <a class="help" href="?set=post_wiki&amp;slug=WikiOrphanPageIndex">?</a></h2>';
		echo '<ul>';
			foreach (posts_process($mpA) as $rcd)
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
		echo '</ul>';

		echo '<h2>Recent changes <a class="help" href="?set=post_wiki&amp;slug=WikiRecentChangesIndex">?</a></h2>';
		$rA = posts_process($DB->queryFetchAll('SELECT * FROM post_wiki ORDER BY _mtime DESC LIMIT 10'));
		echo '<ul>';
			foreach (posts_process($rA) as $rcd)
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
		echo '</ul>';

		echo '<h2>Maintenance tasks <a class="help" href="?set=post_wiki&amp;slug=WikiRecentChangesIndex">?</a></h2>';
		echo wiki_maintenance_refresh_slug_reverse_index_formH(); }

echo '<footer>';
printf('<p><a href="?set=post_wiki">instance main page</a> | <em>time: %.3f </em></p>', microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
