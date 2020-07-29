<?php

function wiki_camel_to_spaced(string $str) : string
{
	return preg_replace('/([a-z0-9])([A-Z])/', '\\1 \\2', $str);
}

function wiki_post_title_to_htmlH(array $rcd) : string
{
	return '<h1>' .H(wiki_camel_to_spaced($rcd['_url_slug'])) .'</h1>';
}

function wiki_posts_readable_by_slugP(string $slug) : bool
{
	$DB = db_pdo();

	return count($DB->queryFetchAll('SELECT _url_slug FROM post_wiki WHERE _url_slug = ? LIMIT 1', [ $slug ])) > 0;
}

function wiki_post_edit_formH(array $rcd) : string
{
	return '
		<form>
			<input type="hidden" name="set" value="post_wiki"/>
			<input type="hidden" name="slug" value="' .H($rcd['_url_slug']) .'"/>
			<button name="form" value="edit">Edit ' .H($rcd['_url_slug']) .'</button>
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

function wiki_post_body_to_htmlH(array $rcd) : string
{
	$str = $rcd['body'];
	return '<p>' .implode('</p><p>', array_map('wiki_text_to_linkedH', array_map('H', explode("\n\n", $str)))) .'</p>';
}

function wiki_post_to_linked_slugs(array $rcd) : array
{
	$matches = [];
	preg_match_all(wiki_slug_re(), $rcd['body'], $matches);
	return array_unique($matches[1]);
td(compact('rcd', 'matches', 'a'));
}
