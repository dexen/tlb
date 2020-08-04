<?php

require '../init.php';

http_cache_prevent();

$DB = db_pdo();

$action = $_POST['action']??null;
$query = null;
$sA = [];
$rcd = null;
$set = $_GET['set']??null;
$slug = $_GET['slug']??null;
$service = $_GET['service']??null;
$form = $_GET['form']??null;
$post_data = $_POST['data']??null;

if ($set === 'post_wiki') {
	$rcd = $DB->queryFetch('SELECT * FROM post_wiki WHERE _url_slug = ?', [ $slug ]);
}

if ($service === 'TlbConfig') {
	if (($_POST['action']??null) === 'save-federation-connections') {
		$data = $_POST['data'];
		wiki_config_save('federation.connections', lf($data['federation.connections']));
		header('Location: ?set=post_wiki&slug=TlbConfiguration#TlbFederationConnections');
		die(); } }

if ($set === null)
	die(header('Location: ?set=post_wiki'));

if (($set === 'post_wiki') && ($slug === null))
	die(header('Location: ?set=post_wiki&slug=WelcomeWikiVisitors'));

if ($set === 'post_wiki') {
	if (strncmp($service, 'WikiSearch', 10) === 0)
		$query = $_GET['query']??null;

	$qq = '%' .$query .'%';

	if ($service === 'WikiSearchSlug')
		$sA = $DB->queryFetchAll('SELECT * FROM post_wiki WHERE _url_slug LIKE ? ', [ $qq ] );
	else if ($action === 'WikiSearchContent')
		$sA = $DB->queryFetchAll('SELECT * FROM post_wiki WHERE (_url_slug || \' \' || body) LIKE ? ', [ $qq ] );

	if (count($sA) === 1)
		die(header('Location: ?set=post_wiki&slug=' .U(array_one($sA)['_url_slug']) .'&service=WikiSearchResultSingle&query=' .U($query))); }

if ($set === 'post_wiki') {
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
						[ lf($_POST['body']), $slug, Uuid::generateUuidV4() ] );
				else if (empty($_POST['body']))
					$DB->execParams('
						DELETE FROM post_wiki WHERE _url_slug = ? ',
						[ $slug ] );
				else
					$DB->execParams('UPDATE post_wiki SET body = ? WHERE _url_slug = ?',
						[ lf($_POST['body']), $slug ]);

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
echo '<html lang="' .H(tlb_config('i18n.lang')) .'">';
echo '<head>';
echo '
<style>
	html, body {
		background: #bbc3bb; }

	a {
		line-height: 120%; }

	a.broken-link {
		color: #d00; }

	a.broken-link:visited {
		color: #b00; }

	li {
		margin-bottom: .58rem; }

	.columns {
		display: flex;
		flex-wrap: wrap; }

	.column-4 {
		flex-grow: 4;
		flex-shrink: 0;
		flex-basis: 60ch; }

	body {
		margin: 0; }

	button.strut-12 {
		min-height: 6ex;
		min-width: 100%; }

	* {
		box-sizing: border-box;  }

	.page-with-shadow {
		margin: 4px;
		background: white;
		box-shadow: 2px 1px 4px rgba(0, 0, 0, 0.33); }

	.connection-box {
		box-shadow: inset 0px 0px 4ex 0px #c4e8ff; }

	.htmlike {
		display: flow-root; }

	.bodylike {
		margin: .5rem; }

	.content-body img {
		max-width: 100%; }

		/* display:none; would prevent submisson */
	button.carryover-submit {
		overflow: hidden;
		visibility: hidden;
		margin: 0;
		border: 0;
		padding: 0;
		width: 0;
		height: 0;
		display: inline-block; }

</style>';
echo '<meta charset="utf-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
if ($rcd)
	echo '<meta name="description" content="' .H(wiki_post_meta($rcd)) .'">';
echo '<title>' .H(wiki_camel_to_spaced($rcd['_url_slug']??null)) .'</title>';
echo '</head>';
echo '<body>';

echo '<div class="columns">';
echo '<div class="column-4">';
echo '<div class="page-with-shadow">';
echo '<div class="htmlike">';
echo '<section class="bodylike">';

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
		echo '<h1><a href="?set=post_wiki"><img
			alt="TlbInstance at ' .H(tlb_address()) .'"
			src="visual-hash-png.php?size=32&amp;id=' .HU(tlb_address_id()) .'"
			srcset="visual-hash-png.php?size=64&amp;id=' .HU(tlb_address_id()) .' 2x,
				visual-hash-png.php?size=96&amp;id=' .HU(tlb_address_id()) .' 3x,
				visual-hash-png.php?size=128&amp;id=' .HU(tlb_address_id()) .' 4x"
			width="32" height="32"/></a> ' .H(wiki_slug_to_title($rcd['_url_slug'])) .'</h1>';
		echo '<div class="content-body">';
			echo wiki_post_body_to_htmlH($rcd);
		echo '</div>';
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

	echo '<h1>Wiki services <a href="?set=post_wiki&amp;slug=TlbWikiService">?</a></h1>';
	echo '<h2>Reverse index <a class="help" href="?set=post_wiki&amp;slug=WikiReverseSlugIndex">?</a></h2>';
		echo '<ul>';
			foreach (posts_process($riA) as $rcd) {
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
			}
		echo '</ul>';

	echo '<form>';
		echo '<label>
			<h2>Search <a class="help" href="?set=post_wiki&amp;slug=WikiSearch">?</a></h2>';

		echo '<input name="query" placeholder="query" value="' .H($query) .'" ' .($query?'autofocus':null) .'/></label>';
		if ($service)
			echo '<button type="submit" class="carryover-submit" name="service" value="' .H($service) .'">carryover :-)</button>';
		echo '<button name="service" value="WikiSearchSlug" type="submit">slug</button>';
		echo ' | ';
		echo '<button name="service" value="WikiSearchContent" type="submit">content</button>';
		echo '<input type="hidden" name="set" value="post_wiki"/><input type="hidden" name="slug" value="' .H($_GET['slug']??null) .'"/>';
	echo '</form>';

	if (($query !== null) && empty($sA))
		echo '<p><em>no matches</em></p>';
	else {
		echo '<ul>';
			foreach (posts_process($sA) as $rcd)
				echo '<li><a href="', H($rcd['_url_canonical']), '&amp;service=' .HU($service) .'&amp;query=' .HU($query) .'">', H($rcd['_link_text_default']), '</a></li>';
		echo '</ul>'; }

	if ($slug === 'WikiPostIndex') {
		$a = posts_process($DB->queryFetchAll('SELECT * FROM post_wiki ORDER BY _url_slug'));
		echo '<h2>Post index</h2>';
		if (empty($a))
			echo '<em>no wiki posts</em>';
		echo '<ul>';
			foreach ($a as $rcd) {
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
			}
		echo '</ul>'; }

	if ($slug === 'TlbMaintenaceTask') {
		echo '<h2>Maintenance tasks <a class="help" href="?set=post_wiki&amp;slug=TlbMaintenaceTask">?</a></h2>';
		echo wiki_maintenance_refresh_slug_reverse_index_formH(); }

	if ($slug === 'WikiOrphanPageIndex') {
		$mpA = $DB->queryFetchAll('
			SELECT p._url_slug
			FROM post_wiki AS p
			LEFT JOIN _wiki_slug_use AS u USING(_url_slug)
			WHERE u._url_slug IS NULL
			ORDER BY p._mtime DESC' );
		echo '<h2>Orphan pages <a class="help" href="?set=post_wiki&amp;slug=WikiOrphanPageIndex">?</a></h2>';
		echo '<ul>';
			foreach (posts_process($mpA) as $rcd)
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
		echo '</ul>'; }

	if ($slug === 'WikiMissingPagesIndex') {
		$mpA = $DB->queryFetchAll('
			SELECT p._url_slug
			FROM post_wiki AS p
			LEFT JOIN _wiki_slug_use AS u ON p._url_slug = u._url_slug
			WHERE u._url_slug IS NULL' );

		echo '<h2>Missing pages <a class="help" href="?set=post_wiki&amp;slug=WikiMissingPagesIndex">?</a></h2>';
		echo '<ul>';
			foreach (posts_process($mpA) as $rcd)
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
		echo '</ul>'; }

	if ($slug === 'WikiRecentChangesIndex') {
		echo '<h2>Recent changes <a class="help" href="?set=post_wiki&amp;slug=WikiRecentChangesIndex">?</a></h2>';
		$rA = posts_process($DB->queryFetchAll('SELECT * FROM post_wiki ORDER BY _mtime DESC LIMIT 10'));
		echo '<ul>';
			foreach (posts_process($rA) as $rcd)
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
		echo '</ul>'; }

	if ($slug === 'TlbConfiguration') {
		echo '<h2>Configuration <a class="help" href="?set=post_wiki&amp;slug=TlbConfiguration">?</a></h2>';
		echo '<form method="post" action="?service=TlbConfig">';
			echo '<label id="TlbFederationConnections">TlbFederationConnections  <a class="help" href="?set=post_wiki&amp;slug=TlbFederationConnections">?</a><br>';
			echo '<textarea name="data[federation.connections]" rows="7" cols="40" style="min-width: 100%">' .H(tlb_config('federation.connections')) .'</textarea></label>';
			echo '<button type="submit" name="action" value="save-federation-connections" class="strut-12">Save</button>';
		echo '</form>';
	}

echo '</section>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="column-4">';
echo '<div class="page-with-shadow">';
echo '<div class="htmlike">';
echo '<section class="bodylike">';
	echo '--';
echo '</section>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="column-4">';
	foreach (tlb_connections() as $c) {
		unset($crcd);
		echo '<div class="page-with-shadow">';
		echo '<div class="connection-box htmlike">';
		echo '<section class="bodylike">';
			if (($_GET['form']??null) === 'edit') {
				$crcd = wiki_rcd_relevant_from_connection($c, $slug);
				echo '<fieldset>';
					echo '<label>body:<br>
						<textarea style="width: 100%" rows="', H($rows), '">' .H($crcd['body']) .'</textarea></label>';
				echo '</fieldset>'; }

			echo '<h2><a href="' .H(wiki_connection_post_url($c, $slug)) .'"><img
				alt="TlbInstance at ' .H($c) .'"
				src="visual-hash-png.php?size=32&amp;id=' .HU(tlb_address_id($c)) .'"
				srcset="visual-hash-png.php?size=64&amp;id=' .HU(tlb_address_id($c)) .' 2x,
					visual-hash-png.php?size=96&amp;id=' .HU(tlb_address_id($c)) .' 3x,
					visual-hash-png.php?size=128&amp;id=' .HU(tlb_address_id($c)) .' 4x"
				width="32" height="32"/></a> Connection: ' .H($c) .'</h2>';
			http_flush();

			if (!isset($crcd))
				$crcd = wiki_rcd_relevant_from_connection($c, $slug);

			echo '<div class="content-body">';
				$v = wiki_post_body_to_htmlH($crcd);
				if ($v === '<p></p>')
					echo '<p><em>--</em></p>';
				else
					echo $v;
			echo '</div>';

			echo '<h2>Reverse index <a class="help" href="?set=post_wiki&amp;slug=WikiReverseSlugIndex">?</a></h2>';
				echo '<ul>';
					foreach (posts_process(wiki_reverse_index_from_connection($c, $slug)) as $rcd)
						echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
				echo '</ul>';
		echo '</section>';
		echo '</div>';
		echo '</div>';
		http_flush(); }
echo '</div>';
echo '</div>';
echo '</div>';

echo '<footer class="">';
echo '<div class="page-with-shadow">';
echo '<div class="htmlike">';
echo '<section class="bodylike">';
printf('<p><a href="?set=post_wiki">instance main page</a> | <em>time: %.3f </em></p>', microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
