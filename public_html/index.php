<?php

require '../init.php';


$DB = db_pdo();

if (array_key_exists('slug', $_GET)) {
	$rcd = $DB->queryFetch('SELECT * FROM post_wiki WHERE _url_slug = ?', [ $_GET['slug']??null ]);

	if ($rcd === null)
		header('HTTP/1.1 404'); }

echo '<!DOCTYPE html>';
echo '<html>';
echo '<body>';

if (array_key_exists('slug', $_GET)) {
	if ($rcd) {
		echo wiki_post_title_to_html($rcd);
		echo wiki_post_body_to_html($rcd); }
	else {
		echo '<h1>Wiki entry not found</h1>';
		echo '<p><em>The wiki entry has not been found. Create?</em></p>'; } }

if (noz($_GET['slug']??null) === null) {
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
