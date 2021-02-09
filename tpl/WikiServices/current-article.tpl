<?php

	$riA = $DB->queryFetchAll('
		SELECT w.*
		FROM wiki AS w
		JOIN _wiki_slug_use AS u ON w.slug = u.from_slug
		WHERE u.to_slug= ?', [ $_GET['slug']??null ]);

	echo '<h3>Reverse index <a class="help" href="?set=post_wiki&amp;slug=TlbWikiReverseSlugIndex">?</a></h3>';
		echo '<ul>';
			foreach (posts_process($riA) as $rcd) {
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
			}
		echo '</ul>';

	echo '<form>';
		echo '<label>
			<h3>Search <a class="help" href="?set=post_wiki&amp;slug=TlbWikiSearch">?</a></h3>';

		echo '<input name="query" placeholder="query" value="' .H($query) .'" ' .($query?'autofocus':null) .'/></label>';
		if ($service)
			echo '<button type="submit" class="carryover-submit" name="service" value="' .H($service) .'">carryover :-)</button>';
		echo '<button name="service" value="TlbWikiSearchSlug" type="submit">slug</button>';
		echo ' | ';
		echo '<button name="service" value="TlbWikiSearchContent" type="submit">content</button>';
		echo '<input type="hidden" name="set" value="post_wiki"/><input type="hidden" name="slug" value="' .H($_GET['slug']??null) .'"/>';
	echo '</form>';

	if (($query !== null) && ($shortcut === 'single-hit'))
		echo '<p><em>opened the sole match</em></p>';
	else if (($query !== null) && empty($sA)) {
		echo '<p><em>no matches</em>';
		if (wiki_contains_slugP($query))
			echo ', ' .wiki_slug_to_linkH($query, 'go create ' .$query .'?');
		echo '</p>'; }
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

	if (($slug === 'TlbMaintenaceTask') || ($slug === 'TlbWikiReverseSlugIndex')) {
		echo '<h3>Maintenance tasks <a class="help" href="?set=post_wiki&amp;slug=TlbMaintenaceTask">?</a></h3>';
		echo wiki_maintenance_refresh_slug_reverse_index_formH(); }

	if ($slug === 'TlbWikiOrphanPageIndex') {
		$opA = $DB->queryFetchAll('
			SELECT w.slug
			FROM wiki AS w
			LEFT JOIN _wiki_slug_use AS u ON w.slug = u.to_slug
			WHERE u.to_slug IS NULL' );

		echo '<h3>Orphan pages <a class="help" href="?set=post_wiki&amp;slug=TlbWikiOrphanPageIndex">?</a></h3>';
		echo '<ul>';
			foreach (posts_process($opA) as $rcd)
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
		echo '</ul>'; }

	if ($slug === 'TlbWikiMissingPagesIndex') {
		$mpA = $DB->queryFetchAll('
			SELECT u.to_slug AS slug
			FROM _wiki_slug_use AS u
			LEFT JOIN wiki AS w ON u.to_slug = w.slug
			WHERE w.slug IS NULL
			GROUP BY u.to_slug' );

		echo '<h3>Missing pages <a class="help" href="?set=post_wiki&amp;slug=TlbWikiMissingPagesIndex">?</a></h3>';
		echo '<ul>';
			foreach (posts_process($mpA) as $rcd)
				echo '<li><a href="', H($rcd['_url_canonical']), '">', H($rcd['_link_text_default']), '</a></li>';
		echo '</ul>'; }

	if ($slug === 'TlbWikiRecentChangesIndex') {
		echo '<h3>Recent changes <a class="help" href="?set=post_wiki&amp;slug=TlbWikiRecentChangesIndex">?</a></h3>';
		$rA = posts_process($DB->queryFetchAll('SELECT * FROM wiki ORDER BY mtime DESC LIMIT 10'));
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
