<?php

function wiki_camel_to_spaced(string $str) : string
{
	return preg_replace('/([a-z0-9])([A-Z])/', '\\1 \\2', $str);
}

function wiki_post_title_to_htmlH(array $rcd) : string
{
	return '<h1>' .H(wiki_camel_to_spaced($rcd['_url_slug'])) .'</h1>';
}

function wiki_text_to_linkedH(string $str) : string
{
	return preg_replace('/\\b([A-Z][a-z]+[A-Z][a-z]+[a-zA-Z]+)\\b/', '<a href="?set=post_wiki&amp;slug=\\1">\\1</a>', $str);
}

function wiki_slug_to_linkH(string $slug) : string
{
	return '<a href="?set=post_wiki&amp;slug=' .HU($slug) .'">' .H(wiki_camel_to_spaced($slug)) .'</a>';
}

function wiki_post_body_to_html(array $rcd) : string
{
	$str = $rcd['body'];
	return '<p>' .implode('</p><p>', array_map('wiki_text_to_linkedH', array_map('H', explode("\n\n", $str)))) .'</p>';
}
