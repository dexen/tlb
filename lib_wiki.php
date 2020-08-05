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

function wiki_slug_re() : string
{
	return '/\\b([A-Z][a-z]+[A-Z][a-z]+[a-zA-Z]*)\\b/';
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
function wiki_href_re() : string { return '`(https?://[][a-zA-Z0-9-._~:/?#@!$&\'()*+,;=]+([.]jpe?g|[.]gif|[.]png|[.]svg))|(https?://[a-zA-Z0-9-._~:/?#@!$&\'()*+,;=]+)`'; }

function wiki_links(string $str, array $data) : array # [ $a, $data ]
{
	$str = preg_replace_callback(
		wiki_href_re(),
		function(array $matches) use(&$data) : string
		{
			if ($matches[2]??null)
				$data[] = '<img src="' .$matches[1] .'">';
			else
				$data[] = '<a href="' .$matches[3] .'">' .$matches[3] .'</a>';
			return '%' .count($data) .'$s';
		},
		$str );

	return [ $str, $data ];
}

function wiki_words_to_links(string $str, array $data) : array # [ $a, $data ]
{
	$str = preg_replace_callback(wiki_slug_re(),
		function(array $matches) use(&$data)
		{
			$slug = $matches[1];
			if (wiki_posts_readable_by_slugP($slug))
				$data[] = '<a href="?set=post_wiki&amp;slug=' .HU($slug) .'">'  .$slug .'</a>';
			else
				$data[] = '<a href="?set=post_wiki&amp;slug=' .HU($slug) .'&amp;form=edit" class="broken-link">' .H($slug) .'</a>';
			return '%' .(count($data)) .'$s';
		},
		$str );

	return [ $str, $data ];
}

function wiki_linear_formatting(string $str, array $data) : array # [ $a, $data ]
{
	$str = preg_replace("/'''(.*)'''/", '<b>\\1</b>', $str);
	$str = preg_replace("/''(.*)''/", '<em>\\1</em>', $str);
	$str = preg_replace("/~~(.*)~~/", '<strike>\\1</strike>', $str);
	return [ $str, $data ];
}

function wiki_block_formatting(string $str, array $data) : array # [ $a, $data ]
{
	$ret = [];
	$p = null;

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

	$P = function(string $str) use(&$data)
	{
		$data[] = $str;
		return '%' .count($data) .'$s';
	};

	foreach (explode("\n", $str) as $line) {
		if ($line === '') {
			$ret[] = $uLL(0);
			$ret[] = $ctx(-1); }

		if (preg_match('/^-{4,}(.*)/', $line, $matches)) {
			$ret[] = $uLL(0);
			$ret[] = $ctx(-1);
			$line = $matches[1];
			$ret[] = "<hr>\n"; }

		if (preg_match('/^([*]+)(.*)/', $line, $matches)) {
			$ret[] = $ctx(-1);
			$ret[] = $uLL($level = strlen($matches[1]))
				.$matches[2];
			$line = null; }

		if (preg_match('/^(={1,})(.+)/', $line, $matches)) {
			$ret[] = $ctx(-1);
			$ret[] = $P('<h' .(strlen($matches[1])+1) .'>');
			$ret[] = $matches[2];
			$ret[] = $P('</h' .(strlen($matches[1])+1) .'>');
			$line = null; }

		if (false
				|| preg_match("/^[\t]([^:]+):[\t ](.*)/", $line, $matches)
				|| preg_match("/^[ ]{4,4}([^:]+):[\t ](.*)/", $line, $matches)
				|| preg_match("/^[ ]([ ]):[ ](.*)/", $line, $matches)) {
			$ret[] = $uLL(0);

			$ret[] = $ctx('dl');

			$p = 0;
			$ret[] = $P("<dt>" .H($matches[1]) ."</dt>\n\t<dd>");
				# FixMe - <dd> is a flow element, we should handle that properly
			$ret[] = $matches[2];
			$ret[] = $P("</dd>\n");
			$line = 0; }

		if (preg_match('/^[ \\t](.+)/', $line, $matches)) {
			$ret[] = $uLL(0);
			$ret[] = $P($ctx('pre') .$matches[1] .$ctx(-1));
			$line = null; }

		if ($line) {
			$ret[] = $ctx('p');
			$ret[] = $line ."\n"; } }

		$ret[] = $uLL(0);
		$ret[] = $ctx(-1);

	return [ implode("\n", array_filter($ret, fn($v) => !is_null($v))), $data ];
}

function wiki_post_body_to_htmlH(string $body) : string
{
	$preescape = fn(string $str) => str_replace('%', '%%', $str);

	$XAPPLY = function(string $str, array $data, string $callback) : array /* [ $str, $data ] */
	{
		[$str, $newdata] = $callback($str, $data);
		$data = $data + $newdata;
		return [ $str, $data ];
	};

	$str = $preescape($body);
	$data = [];

	[ $str, $data ] = $XAPPLY($str, $data, 'wiki_encode_html');
	[ $str, $data ] = $XAPPLY($str, $data, 'wiki_links');
	[ $str, $data ] = $XAPPLY($str, $data, 'wiki_words_to_links');
	[ $str, $data ] = $XAPPLY($str, $data, 'wiki_block_formatting');
	[ $str, $data ] = $XAPPLY($str, $data, 'wiki_linear_formatting');

	return vsprintf($str, $data);
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
