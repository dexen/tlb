<?php

function wiki_camel_to_spaced(string $str = null) : ?string
{
	if ($str === null)
		return null;
	return preg_replace('/([a-z0-9])([A-Z])/', '\\1 \\2', $str);
}

function wiki_slug_to_title(string $slug) : string
{
	return wiki_camel_to_spaced($slug);
}

function wiki_posts_readable_by_slugP(string $slug) : bool
{
	$DB = db_pdo();

	return count($DB->queryFetchAll('SELECT _url_slug FROM post_wiki WHERE _url_slug = ? LIMIT 1', [ $slug ])) > 0;
}

function wiki_post_edit_formH(array $rcd) : string
{
	if (wiki_posts_readable_by_slugP($rcd['_url_slug']))
		$action_name = 'Edit';
	else
		$action_name = 'Create';
	return '
		<form>
			<input type="hidden" name="set" value="post_wiki"/>
			<input type="hidden" name="slug" value="' .H($rcd['_url_slug']) .'"/>
			<button name="form" id="xm" value="edit" class="strut-12">' .H($action_name) .' <var>' .H($rcd['_url_slug']) .'</var> <kbd>[^E]</kbd></button>
		</form>' .
		<<<'EOS'
			<script>
			if (window.location.hash === '#article-saved') {
				document.getElementById('xh').classList.add('saved-box');
				history.replaceState(null, null, ' '); }
				function handleCtrlEnterEdit(event) {
			if (event.ctrlKey || event.metaKey) {
				switch (event.key) {
				case 'e':
					event.preventDefault();
					document.getElementById('xm').click();
					break;
				default:
					return true; } }
				};
				document.getElementsByTagName('html')[0].addEventListener('keyup', handleCtrlEnterEdit, false);
			</script>
EOS;
}

function wiki_maintenance_refresh_slug_reverse_index_formH() : string
{
	$action_name = 'Refresh slug reverse index';
	return '
		<form method="post" action="?set=post_wiki&amp;slug=WikiReverseSlugIndex&amp;service=WikiReverseSlugIndex">
			<button name="action" value="rebuild-slug-reverse-index" style="width: 50%; min-height: 8ex">' .H($action_name) .'</button>
		</form>';
}

function wiki_xxx(array $matches) : string
{
	$slug = $matches[1];
	if (wiki_posts_readable_by_slugP($slug))
		return '<a href="?set=post_wiki&amp;slug=' .HU($slug) .'">' .H($slug) .'</a>';
	else
		return '<a href="?set=post_wiki&amp;slug=' .HU($slug) .'&amp;form=edit" class="broken-link">' .H($slug) .'</a>';
}

function wiki_img_re() : string { return '#(https?://[^\\s]+[.](jpe?g|gif|png|svg))#'; }

function wiki_text_to_linkedH(string $str) : string
{
	$str = preg_replace_callback(wiki_img_re(), fn($matches) => '<img src="' .H($matches[1]) .'">', $str);
	return preg_replace_callback(wiki_slug_re(), 'wiki_xxx', $str);
}

function wiki_slug_re() : string
{
	return '/\\b([A-Z][a-z]+[A-Z][a-z]+[a-zA-Z]+)\\b/';
}

function wiki_slug_to_linkH(string $slug) : string
{
	return '<a href="?set=post_wiki&amp;slug=' .HU($slug) .'">' .H(wiki_camel_to_spaced($slug)) .'</a>';
}

function wiki_text_separatorH(string $paraH) : string
{
	if (strncmp($paraH, '----', 4) === 0)
		return '</p><hr><p>' .substr($paraH, 4);
	else
		return $paraH;
}

	# JUNKME
function wiki_para_processH(string $paraH) : string
{
	$paraH = wiki_text_separatorH($paraH);
	return '<p>' .wiki_text_to_linkedH($paraH) .'</p>';
}

function wiki_line_processH(string $line) : string
{
	$ret = [];
	$ter = [];

	$str = $line;

	if ($str === '')
		$ret[] = "</p>\n\n<p>";

	if (preg_match('/^----(.*)/', $line, $matches)) {
		$ret[] = "</p>\n<hr>\n<p>";
		$line = $matches[1]; }

	if (preg_match("/^[\t][ ]:[\t](.*)/", $line, $matches)
		|| preg_match("/^[ ][ ]:[ ](.*)/", $line, $matches)) {
		$ret[] = "<dl><dt></dt>\n\t<dd>";
		$line = $matches[1];
		$ter[] = "</dd></dl>\n"; }

	if (preg_match('/^[*](.*)/', $line, $matches)) {
		$ret[] = "</p>\n<ul>\n\t<li>";
		$line = $matches[1];
		$ter[] = "</li>\n</ul>\n<p>\n"; }

	$ret[] = H($line);

	return implode($ret).implode(array_reverse($ter));
}

function wiki_post_body_to_htmlH(array $rcd) : string
{
	return '<p>' .implode(
		"\n",
		array_map('wiki_line_processH', explode("\n", $rcd['body']) ) ) .'</p>';
}

function wiki_post_to_linked_slugs(array $rcd) : array
{
	$matches = [];
	preg_match_all(wiki_slug_re(), $rcd['body'], $matches);
	return array_unique($matches[1]);
td(compact('rcd', 'matches', 'a'));
}

function wiki_maintenance_rebuild_slug_reverse_index()
{
	$DB = db_pdo();

	$DB->beginTransaction();
		$DB->exec('DELETE FROM _wiki_slug_use');
		$a = $DB->queryFetchAll('SELECT * FROM post_wiki');
		$St = $DB->prepare('INSERT INTO _wiki_slug_use (post_id, _url_slug) VALUES (?, ?)');
		foreach ($a as $rcd)
			foreach (wiki_post_to_linked_slugs($rcd) as $slug)
				$St->execute([ $rcd['post_id'], $slug ]);
	$DB->commit();
}

function wiki_post_meta(array $rcd) : string
{
	return sprintf('Wiki page for %s',
		$rcd['_url_slug'] );
}

function wiki_connection_post_url(string $connection, string $slug) : string
{
	$curl = tlb_connection_url($connection);
	return $curl .'?set=post_wiki&slug=' .U($slug);
}

function wiki_rcd_relevant_from_connection(string $connection, string $_url_slug) : array
{
	$curl = tlb_connection_url($connection);
	$url = $curl .'/wiki-text.php?slug=' .U($_url_slug);
	$body = tlb_download_connection($url);
	return compact('_url_slug', 'body');
}

function wiki_reverse_index_from_connection(string $connection, string $_url_slug) : array
{
	$curl = tlb_connection_url($connection);
	$url = $curl .'/wiki-reverse-index.php?slug=' .U($_url_slug);
	$slugA = array_filter(explode(',', tlb_download_connection($url)), 'strlen');

	$body = null;
	return array_map(
		function($_url_slug) use($body) { return compact('_url_slug', 'body'); },
		$slugA );
}
