<?php

require '../init.php';

echo '<!DOCTYPE html>';
echo '<html>';
echo '<body>';

$DB = db_pdo();

$rcd = $DB->queryFetch('SELECT * FROM post_wiki WHERE _url_slug = ?', [ $_GET['slug']??null ]);

if ($rcd) {
	echo wiki_post_title_to_html($rcd);
	echo wiki_post_body_to_html($rcd); }

$a = posts_process($DB->queryFetchAll('SELECT * FROM post_wiki'));

if (empty($a))
	echo '<em>no wiki posts</em>';
else {
	echo '<ul>';
		foreach ($a as $rcd) {
			echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
		}
	echo '</ul>'; }

echo '<p>ALL DONE.</p>';
