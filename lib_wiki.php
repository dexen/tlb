<?php

function wiki_camel_to_spaced(string $str = null) : ?string
{
	if ($str === null)
		return null;
	return preg_replace('/([a-z\\p{Ll}])([A-Z\\p{Lu}])/u', '\\1 \\2', $str);
}

function wiki_slug_to_title(string $slug) : string
{
	return wiki_camel_to_spaced($slug);
}

function wiki_posts_readable_by_slugP(string $slug, string $subslug = null) : bool
{
	$DB = db_pdo();

	if ($subslug !== null)
		return count($DB->queryFetchAll('SELECT slug FROM post_wiki_note_dated WHERE slug = ? AND date = ? LIMIT 1', [ $slug, $subslug ])) > 0;

	return count($DB->queryFetchAll('SELECT _url_slug FROM post_wiki WHERE _url_slug = ? LIMIT 1', [ $slug ])) > 0;
}

function wiki_maintenance_refresh_slug_reverse_index_formH() : string
{
	$action_name = 'Refresh slug reverse index';
	return '
		<form method="post" action="?set=post_wiki&amp;slug=WikiReverseSlugIndex&amp;service=WikiReverseSlugIndex">
			<button name="action" value="rebuild-slug-reverse-index" class="strut-12">' .H($action_name) .'</button>
		</form>';
}

function wiki_slug_re() : string
{
	return '#\\b([A-Z\\p{Lu}][a-z\\p{Ll}]+([A-Z\\p{Lu}][a-z\\p{Ll}]+)+)([/]([0-9-]+))?\\b#u';
}

function wiki_slug_to_linkH(string $slug) : string
{
	return '<a href="?set=post_wiki&amp;slug=' .HU($slug) .'">' .H(wiki_camel_to_spaced($slug)) .'</a>';
}

function wiki_encode_html(string $str, array $data) : array
{
	return [ H($str), $data ];
}

	# https://tools.ietf.org/html/rfc3986#section-2.2
function wiki_href_re() : string { return '`(https?://[][a-zA-Z0-9-._~:/?#@!$&\'()*+,;=%]+([.]jpe?g|[.]gif|[.]png|[.]svg))|(https?://[a-zA-Z0-9-._~:/?#@!$&\'()*+,;=%]+)`'; }

function wiki_links(string $str, array $data) : array # [ $a, $data ]
{
	$DC = function(string $str) : string
	{
		return str_replace('%%', '%', $str);
	};

	$str = preg_replace_callback(
		wiki_href_re(),
		function(array $matches) use(&$data, $DC) : string
		{
			if ($matches[2]??null)
				$data[] = '<img src="' .H($DC($matches[1])) .'">';
			else
				$data[] = '<a href="' .H($DC($matches[3])) .'">' .H($DC($matches[3])) .'</a>';
			return '%' .count($data) .'$s';
		},
		$str );

	return [ $str, $data ];
}

function wiki_slugs_to_links(string $str, array $data) : array # [ $a, $data, $slugs ]
{
	$slugs = [];

	$str = preg_replace_callback(wiki_slug_re(),
		function(array $matches) use(&$data, &$slugs)
		{
			$slugs[] = $slug = $matches[1];
			$subslug = $matches[4]??null;
			if ($subslug === null)
				$hxU = $cx = null;
			else
				[ $hxU, $cx ] = [ 'subslug=' .U($matches[4]), '/' .$matches[4] ];

			if (wiki_posts_readable_by_slugP($slug, $subslug))
				$data[] = '<a href="?set=post_wiki&amp;slug=' .HU($slug) .'&amp;' .H($hxU) .'">'  .H($slug .$cx) .'</a>';
			else
				$data[] = '<a href="?set=post_wiki&amp;slug=' .HU($slug) .'&amp;' .H($hxU) .'&amp;form=edit" class="broken-link">' .H($slug .$cx) .'</a>';
			return '%' .(count($data)) .'$s';
		},
		$str );

	return [ $str, $data, $slugs ];
}

function wiki_linear_formatting(string $str, array $data) : array # [ $a, $data ]
{
	$DC = fn(string $str) => str_replace('%%', '%', $str);

	$str = preg_replace_callback(
		"/`([^`]+)`/U",
		function ($matches) use(&$data, $DC)
		{
			$data[] = '<code>';
			$data[] = H($DC($matches[1]));
			$data[] = '</code>';
			return '%' .(count($data)-2) .'$s' .'%' .(count($data)-1) .'$s' .'%' .count($data) .'$s';
		},
		$str );

	$str = preg_replace_callback(
		"/'''(.*)'''/U",
		function ($matches) use(&$data)
		{
			$data[] = '<b>';
			$data[] = '</b>';
			return '%' .(count($data)-1) .'$s' .$matches[1] .'%' .count($data) .'$s';
		},
		$str );

	$str = preg_replace_callback(
		"/''(.*)''/U",
		function ($matches) use(&$data)
		{
			$data[] = '<em>';
			$data[] = '</em>';
			return '%' .(count($data)-1) .'$s' .$matches[1] .'%' .count($data) .'$s';
		},
		$str );

	$str = preg_replace_callback(
		"/~~(.*)~~/U",
		function ($matches) use(&$data)
		{
			$data[] = '<strike>';
			$data[] = '</strike>';
			return '%' .(count($data)-1) .'$s' .$matches[1] .'%' .count($data) .'$s';
		},
		$str );

	return [ $str, $data ];
}

function wiki_block_formatting(string $str, array $data) : array # [ $a, $data ]
{
	$ret = [];
	$p = null;

	$DC = fn(string $str) => str_replace('%%', '%', $str);

	$ctx = function($v = null) : ?string
	{
		static $c;
		$old = $c;

		if ($v === -1) {
			$c = null;
			if ($old)
				return "</$old>\n";
			else
				return null; }

		if ($v !== null)
			$c = $v;
		if ($v !== $old) {
			if ($old)
				return "\n</$old >\n<$c>\n";
			else
				return "<$c>\n"; }
		return null;
	};

		# wait what?
	$uLL = function(int $level = 0) : ?string
	{
		static $u = 0;
		$a = [];

		$level *= 2;
		$lowered = false;

		if ($u)
		if ($level <= $u)
			$a[] = "</li>";
		if ($level)
			if ($level === $u)
				$a[] = str_repeat("\t", max($u-1, 0)) ."<li class=t1>";

		while ($level > $u) {
			$a[] = "\n" .str_repeat("\t", $u++) ."<ul>";
			$a[] = str_repeat("\t", $u++) ."<li>"; }

		$tt = "";
		while ($level < $u) {
			$lowered = true;
			$a[] = str_repeat("\t", ($u-- -2)) ."</ul>";
			if ($u>1)
				$a[] = str_repeat("\t", ($u-- -2)) ."</li>";
			else
				$u--;
		}

		if ($lowered)
		if ($level)
		{
			$a[] = str_repeat("\t", $level-1) ."<li class=t9>";
		}

		if ($lowered && !$level)
			$a[] = '';

		if (empty($a))
			return null;
		return implode("\n", $a);
	};

	$P = function(string $str = null) use(&$data) : ?string
	{
		if ($str === null)
			return null;
		$data[] = $str;
		return '%' .count($data) .'$s';
	};

	foreach (explode("\n", $str) as $line) {
		if ($line === '') {
			$ret[] = $P($uLL(0));
			$ret[] = $P($ctx(-1)); }

		if (preg_match('/^-{4,}(.*)/', $line, $matches)) {
			$ret[] = $P($uLL(0));
			$ret[] = $P($ctx(-1));
			$line = $matches[1];
			$ret[] = $P("<hr>\n"); }

			# <ul>
		if (preg_match('/^([*]+)(.*)/', $line, $matches)) {
			$ret[] = $P($ctx(-1));
			$ret[] = $P($uLL($level = strlen($matches[1])))
				.$matches[2];
			$line = null; }

			# <h2 ... h6>
		if (preg_match('/^(={1,})(.+)/', $line, $matches)) {
			$ret[] = $P($ctx(-1));
			$ret[] = $P('<h' .(strlen($matches[1])+1) .'>');
			$ret[] = $matches[2];
			$ret[] = $P('</h' .(strlen($matches[1])+1) .'>');
			$line = null; }

			# <dl>
			# empty <dt> serves as quotation
		if (false
				|| preg_match("/^[\t]([^:]+):[\t ](.*)/", $line, $matches)
				|| preg_match("/^[ ]{4,4}([^:]+):[\t ](.*)/", $line, $matches)
				|| preg_match("/^[ ]([ ]):[ ](.*)/", $line, $matches)) {
			$ret[] = $P($uLL(0));
			$ret[] = $P($ctx('dl'));

			$p = 0;
			$ret[] = $P("<dt>");
			$ret[] = $matches[1];
			$ret[] = $P("</dt>\n\t<dd>");
				# FixMe - <dd> is a flow element, we should handle that properly
			$ret[] = $matches[2];
			$ret[] = $P("</dd>\n");
			$line = 0; }

			# <pre>
		if (preg_match('/^([ \\t].+)/', $line, $matches)) {
			$ret[] = $P($uLL(0));
			$ret[] = $P($ctx('pre') .H($DC($matches[1])));
			$line = null; }

		if ($line) {
			$ret[] = $P($ctx('p'));
			$ret[] = $line ."\n"; } }

		$ret[] = $P($uLL(0));
		$ret[] = $P($ctx(-1));

	return [ implode("\n", array_filter($ret, fn($v) => !is_null($v))), $data ];
}

function wiki_post_body_to_htmlH(string $body) : string
{
	return _wiki_post_body_processing($body)[0];
}

function wiki_post_body_to_slugs(string $body) : array
{
	return array_unique(_wiki_post_body_processing($body)[1]);
}

function _wiki_post_body_processing(string $body) : array # [ string $html, array $slugs ]
{
	$preescape = fn(string $str) => str_replace('%', '%%', $str);

	$XAPPLY = function(string $str, array $data, string $callback) : array /* [ $str, $data ] */
	{
		$a = $callback($str, $data);
		[$str, $newdata] = $a;
		$data = $data + $newdata;
		$a[0] = $str;
		$a[1] = $data;
		return $a;
	};

	$str = $preescape($body);
	$data = [];

	[ $str, $data ] = $XAPPLY($str, $data, 'wiki_block_formatting');
	[ $str, $data ] = $XAPPLY($str, $data, 'wiki_linear_formatting');
	[ $str, $data ] = $XAPPLY($str, $data, 'wiki_links');
	[ $str, $data, $slugs ] = $XAPPLY($str, $data, 'wiki_slugs_to_links');
	[ $str, $data ] = $XAPPLY($str, $data, 'wiki_encode_html');

	return [ vsprintf($str, $data), $slugs ];
}

function wiki_maintenance_rebuild_slug_reverse_index()
{
	$DB = db_pdo();

	$DB->beginTransaction();
		$DB->exec('DELETE FROM _wiki_slug_use');
		$a = $DB->queryFetchAll('SELECT * FROM wiki');
		$St = $DB->prepare('INSERT INTO _wiki_slug_use (from_slug, to_slug) VALUES (?, ?)');
		foreach ($a as $rcd)
			foreach (wiki_post_body_to_slugs($rcd['body']) as $slug)
				$St->execute([ $rcd['slug'], $slug ]);
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

function wiki_edit_conflict($post_data, $post_original, $latest)
{
	die('edit conflict');
}

function wiki_article_body_redirect_slug(string $body) : ?string
{
	$slug1 = preg_match('#^redirect:(.+)#', $body, $matches1)
		? $matches1[1]
		: null;
	preg_match(wiki_slug_re(), $slug1, $matches2);
	return (($matches2[0]??null) === ($matches2[1]??-1))
		? $slug1
		: null;
}
