<?php

require '../init.php';

$DB = db_pdo();

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
