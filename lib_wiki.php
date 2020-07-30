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
			<button name="form" value="edit" style="width: 50%; min-height: 8ex">' .H($action_name) .' <var>' .H($rcd['_url_slug']) .'</var></button>
		</form>';
}

function wiki_maintenance_refresh_slug_reverse_index_formH() : string
{
	$action_name = 'Refresh slug reverse index';
	return '
		<form method="post" action="?set=post_wiki&amp;form=maintenance">
			<button name="action" value="rebuild-slug-reverse-index" style="width: 50%; min-height: 8ex">' .H($action_name) .'</button>
		</form>';
}

function wiki_xxx(array $matches) : string
{
	$slug = $matches[1];
	if (wiki_posts_readable_by_slugP($slug))
		return '<a href="?set=post_wiki&amp;slug=' .HU($slug) .'">' .H($slug) .'</a>';
	else
		return '<a href="?set=post_wiki&amp;slug=' .HU($slug) .'&amp;action=edit" style="color: red;">' .H($slug) .'</a>';
}

function wiki_text_to_linkedH(string $str) : string
{
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
		return '<hr>' .substr($paraH, 4);
	else
		return $paraH;
}

function wiki_para_processH(string $paraH) : string
{
	$paraH = wiki_text_separatorH($paraH);
	return '<p>' .wiki_text_to_linkedH($paraH) .'</p>';
}

function wiki_post_body_to_htmlH(array $rcd) : string
{
	$str = $rcd['body'];
	return implode(array_map('wiki_para_processH', array_map('H', explode("\n\n", $str))));
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
