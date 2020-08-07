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
$post_meta = $_POST['meta']??null;

if ($set === 'post_wiki') {
	$rcd = $DB->queryFetch('SELECT * FROM post_wiki WHERE _url_slug = ?', [ $slug ]);
	if ($service === 'WikiPageEditor')
		ex('../libexec/post_wiki/WikiPageEditor.php', compact('action', 'service', 'form', 'slug', 'rcd', 'post_data', 'post_meta'));
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
	else if ($service === 'WikiSearchContent')
		$sA = $DB->queryFetchAll('SELECT * FROM post_wiki WHERE (_url_slug || \' \' || body) LIKE ? ', [ $qq ] );

	if (count($sA) === 1)
		die(header('Location: ?set=post_wiki&slug=' .U(array_one($sA)['_url_slug']) .'&service=WikiSearchResultSingle&query=' .U($query))); }

	if ($service === 'WikiReverseSlugIndex') {
		if (($_POST['action']??null) === 'rebuild-slug-reverse-index')
			wiki_maintenance_rebuild_slug_reverse_index();
			echo '<a href="?">ALL DONE.</a>'; die(); }

if (array_key_exists('slug', $_GET)) {
	$rcd = $DB->queryFetch('SELECT * FROM post_wiki WHERE _url_slug = ?', [ $_GET['slug']??null ]);

	if (($_GET['form']??null) === 'edit') {
		if ($rcd)
			$slug = $rcd['_url_slug'];
		else
			$slug = $_GET['slug'] ?? null;
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

	html {
		overflow-y: scroll; }

	body {
		margin: 0; }

	button.strut-12 {
		min-height: 6ex;
		min-width: 100%; }

	button.strut-6 {
		min-height: 6ex;
		min-width: 50%; }

	button.strut-right {
		text-aling: right;
	}

	* {
		box-sizing: border-box;  }

	.page-with-shadow {
		margin: 4px;
		background: white;
		box-shadow: 2px 1px 4px rgba(0, 0, 0, 0.33); }

	.connection-box {
		box-shadow: inset 0px 0px 4ex 0px #c4e8ff; }

	.saved-box {
		animation: htmlikeCompletedSave 2.4s 1; }

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

	textarea.inputAwaitingSave {
		animation: inputAwaitingSaveIntro .2s 1;
		animation: inputAwaitingSave .4s infinite alternate;
	}

	textarea.inputCompletedSave {
		animation: inputCompletedSave 1.2s 1;
	}

	textarea:invalid {
		background: red;
	}

	@keyframes inputAwaitingSaveIntro {
		to { background-color: #bbb; }
	}

	@keyframes inputAwaitingSave {
		from { background-color: #ddd; }
		to { background-color: #bbb; }
	}

	@keyframes inputCompletedSave {
		from { background-color: #afa; }
		to { background-color: #fff; }
	}

	@keyframes htmlikeCompletedSave {
		from { box-shadow: inset 0px 0px 4ex 0px #afa; }
		to { box-shadow: inset 0px 0px 4ex 0px #fff; } }

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
echo '<div class="htmlike" id="xh">';
echo '<div class="bodylike">';

if (($_GET['form']??null) === 'edit') {
	if ($rcd)
		$slug = $rcd['_url_slug'];
	else
		$slug = $_GET['slug'] ?? null;

	echo '<form method="post" action="?set=post_wiki&amp;slug=', HU($slug) ,'&amp;service=WikiPageEditor&amp;form=edit" enctype="multipart/form-data" ' ?>

		onkeydown="
			if (event.key === 'Escape') {
				var TA = document.getElementById('xa');
				if (TA.selectionStart != TA.selectionEnd) {
					var next = TA.selectionStart-1;
					TA.value = TA.value.slice(0, TA.selectionStart-1) + TA.value.slice(TA.selectionEnd);
					TA.selectionEnd = TA.selectionStart = next; }
				else
					TA.selectionStart = document.getElementById('x1').value; }

			if (event.ctrlKey || event.metaKey) {
				switch (event.key) {
				case 's':
					event.preventDefault();
					document.getElementById('xs').click();
					break;
				case 'e':
					event.preventDefault();
					document.getElementById('xe').click();
					break;
				case 'Enter':
					event.preventDefault();
					document.getElementById('xm').click();
					break;
				default:
					return true; }
			}
		"

		onsubmit="
			document.getElementById('xa').classList.add('inputAwaitingSave');
			document.getElementById('xa').readOnly = true;
			document.getElementById('x1').value = document.getElementById('xa').selectionStart;
			document.getElementById('x9').value = document.getElementById('xa').selectionEnd;
			return true;
		"

	<?php
		echo '>';

		echo '<article>';
		echo '<h1><a href="?set=post_wiki"><img
			alt="TlbInstance at ' .H(tlb_address()) .'"
			src="visual-hash-png.php?size=32&amp;id=' .HU(tlb_address_id()) .'"
			srcset="visual-hash-png.php?size=64&amp;id=' .HU(tlb_address_id()) .' 2x,
				visual-hash-png.php?size=96&amp;id=' .HU(tlb_address_id()) .' 3x,
				visual-hash-png.php?size=128&amp;id=' .HU(tlb_address_id()) .' 4x"
			width="32" height="32"/></a> ' .H(wiki_slug_to_title($slug)) .'</h1>';

		echo '<input id="x1" type="hidden" name="meta[selectionStart]" value="' .H($_GET['selectionStart']??null) .'"/>';
		echo '<input id="x9" type="hidden" name="meta[selectionEnd]" value="' .H($_GET['selectionEnd']??null) .'"/>';

		$rows = max(count(explode("\n", $rcd['body']??null))+3, 20);
		echo '<textarea id="xa" name="data[body]" style="width: 100%" ';
		echo ' rows="', H($rows), '">', H($rcd['body']??null), '</textarea>';
		echo '</article>';

		echo '<p style="text-align: right">
			<button type="submit" id="xs" name="action" value="save-edit" class="strut-6 strut-right">Save & keep editing <kbd>[^S]</kbd></var></button>
		</p>';

		echo '<p>
			<button type="submit" id="xe" name="action" value="save-see" class="strut-12">Save <var>' .H($slug) .'</var> <kbd>[^E]</kbd></button>
		</p>';
	echo '</form>';
	echo <<<'EOS'
		<script>
			if (window.location.hash === '#article-saved-continue') {
				document.getElementById('xa').classList.add('inputCompletedSave');
				history.replaceState(null, null, ' '); }

			document.getElementById('xa').focus();
			document.getElementById('xa').setSelectionRange(-1, -1);
			if (String(document.getElementById('x1').value).length)
			document.getElementById('xa').setSelectionRange(
				document.getElementById('x1').value,
				document.getElementById('x9').value );

		</script>
EOS; }

else if ($slug !== null) {
	if ($rcd) {
echo '<article>';
		echo '<h1><a href="?set=post_wiki"><img
			alt="TlbInstance at ' .H(tlb_address()) .'"
			src="visual-hash-png.php?size=32&amp;id=' .HU(tlb_address_id()) .'"
			srcset="visual-hash-png.php?size=64&amp;id=' .HU(tlb_address_id()) .' 2x,
				visual-hash-png.php?size=96&amp;id=' .HU(tlb_address_id()) .' 3x,
				visual-hash-png.php?size=128&amp;id=' .HU(tlb_address_id()) .' 4x"
			width="32" height="32"/></a> ' .H(wiki_slug_to_title($rcd['_url_slug'])) .'</h1>';
		echo '<div class="content-body">'; echo "\n\n";
			echo wiki_post_body_to_htmlH($rcd['body']);
		echo '</div>';
echo '</article>'; }
	else {
echo '<article>';
		echo '<h1><a href="?set=post_wiki"><img
			alt="TlbInstance at ' .H(tlb_address()) .'"
			src="visual-hash-png.php?size=32&amp;id=' .HU(tlb_address_id()) .'"
			srcset="visual-hash-png.php?size=64&amp;id=' .HU(tlb_address_id()) .' 2x,
				visual-hash-png.php?size=96&amp;id=' .HU(tlb_address_id()) .' 3x,
				visual-hash-png.php?size=128&amp;id=' .HU(tlb_address_id()) .' 4x"
			width="32" height="32"/></a> HTTP 404 Not Found</h1>';
echo '</article>';
		echo '<p><em>The wiki entry for ' .wiki_slug_to_linkH($slug) . ' has not been found. Create?</em></p>'; }
		tpl('tpl/WikiPageEditor/form.tpl', ['rcd' => ['_url_slug'=>$slug],
			'selectionStart' => $_GET['selectionStart']??null, 'selectionEnd' => $_GET['selectionEnd']??null ] ); }

	$riA = $DB->queryFetchAll('
		SELECT p.*
		FROM post_wiki AS p
		JOIN _wiki_slug_use AS u ON p.post_id = u.post_id
		WHERE u._url_slug= ?', [ $_GET['slug']??null ]);

#	echo '<h2>Wiki services <a href="?set=post_wiki&amp;slug=TlbWikiService">?</a></h2>';
	echo '<h3>Reverse index <a class="help" href="?set=post_wiki&amp;slug=WikiReverseSlugIndex">?</a></h3>';
		echo '<ul>';
			foreach (posts_process($riA) as $rcd) {
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
			}
		echo '</ul>';

	echo '<form>';
		echo '<label>
			<h3>Search <a class="help" href="?set=post_wiki&amp;slug=WikiSearch">?</a></h3>';

		echo '<input name="query" placeholder="query" value="' .H($query) .'" ' .($query?'autofocus':null) .'/></label>';
		if ($service)
			echo '<button type="submit" class="carryover-submit" name="service" value="' .H($service) .'">carryover :-)</button>';
		echo '<button name="service" value="WikiSearchSlug" type="submit">slug</button>';
		echo ' | ';
		echo '<button name="service" value="WikiSearchContent" type="submit">content</button>';
		echo '<input type="hidden" name="set" value="post_wiki"/><input type="hidden" name="slug" value="' .H($_GET['slug']??null) .'"/>';
	echo '</form>';

	if (($query !== null) && ($service === 'WikiSearchResultSingle'))
		echo '<p><em>opened the sole match</em></p>';
	else if (($query !== null) && empty($sA))
		echo '<p><em>no matches</em></p>';
	else {
		echo '<ul>';
			foreach (posts_process($sA) as $rcd)
				echo '<li><a href="', H($rcd['_url_canonical']), '&amp;service=' .HU($service) .'&amp;query=' .HU($query) .'">', H($rcd['_link_text_default']), '</a></li>';
		echo '</ul>'; }

	if ($slug === 'WikiPostIndex') {
		$a = posts_process($DB->queryFetchAll('SELECT * FROM post_wiki ORDER BY _url_slug'));
		echo '<h3>Post index</h3>';
		if (empty($a))
			echo '<em>no wiki posts</em>';
		echo '<ul>';
			foreach ($a as $rcd) {
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
			}
		echo '</ul>'; }

	if (($slug === 'TlbMaintenaceTask') || ($slug === 'WikiReverseSlugIndex')) {
		echo '<h3>Maintenance tasks <a class="help" href="?set=post_wiki&amp;slug=TlbMaintenaceTask">?</a></h3>';
		echo wiki_maintenance_refresh_slug_reverse_index_formH(); }

	if ($slug === 'WikiOrphanPageIndex') {
		$mpA = $DB->queryFetchAll('
			SELECT p._url_slug
			FROM post_wiki AS p
			LEFT JOIN _wiki_slug_use AS u USING(_url_slug)
			WHERE u._url_slug IS NULL
			ORDER BY p._mtime DESC' );
		echo '<h3>Orphan pages <a class="help" href="?set=post_wiki&amp;slug=WikiOrphanPageIndex">?</a></h3>';
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

		echo '<h3>Missing pages <a class="help" href="?set=post_wiki&amp;slug=WikiMissingPagesIndex">?</a></h3>';
		echo '<ul>';
			foreach (posts_process($mpA) as $rcd)
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
		echo '</ul>'; }

	if ($slug === 'WikiRecentChangesIndex') {
		echo '<h3>Recent changes <a class="help" href="?set=post_wiki&amp;slug=WikiRecentChangesIndex">?</a></h3>';
		$rA = posts_process($DB->queryFetchAll('SELECT * FROM post_wiki ORDER BY _mtime DESC LIMIT 10'));
		echo '<ul>';
			foreach (posts_process($rA) as $rcd)
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
		echo '</ul>'; }

	if ($slug === 'TlbConfiguration') {
		echo '<h3>Configuration <a class="help" href="?set=post_wiki&amp;slug=TlbConfiguration">?</a></h3>';
		echo '<form method="post" action="?service=TlbConfig">';
			echo '<label id="TlbFederationConnections">TlbFederationConnections  <a class="help" href="?set=post_wiki&amp;slug=TlbFederationConnections">?</a><br>';
			echo '<textarea name="data[federation.connections]" rows="7" cols="40" style="min-width: 100%">' .H(tlb_config('federation.connections')) .'</textarea></label>';
			echo '<button type="submit" name="action" value="save-federation-connections" class="strut-12">Save</button>';
		echo '</form>';
	}

echo '</div>';
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
					echo '
						<textarea style="width: 100%" rows="', H($rows), '">' .H($crcd['body']) .'</textarea>';
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
				$v = wiki_post_body_to_htmlH($crcd['body']);
				if ($v === '<p></p>')
					echo '<p><em>--</em></p>';
				else
					echo $v;
			echo '</div>';

			echo '<h3>Reverse index <a class="help" href="?set=post_wiki&amp;slug=WikiReverseSlugIndex">?</a></h3>';
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

echo '<footer class="">';
echo '<div class="page-with-shadow">';
echo '<div class="htmlike">';
echo '<section class="bodylike">';
printf('<p><a href="?set=post_wiki">instance main page</a> | <em>time: %.3f </em></p>', microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
