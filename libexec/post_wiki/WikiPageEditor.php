<?php

		if (($action === 'save-edit') || ($action === 'save-see')) {
			$DB->beginTransaction();
				$DB->execParams('
					DELETE FROM _wiki_slug_use
					WHERE from_slug IN (?)', [ $slug ]);

				$latest = $DB->queryFetch('SELECT * FROM wiki WHERE slug = ?', [$slug]);
#if (preg_match('/bbb/', $post_data['body']))td(compact('post_original', 'latest'));
				if (lf($post_original['body']) !== lf($latest['body']??'')) {
					header_response_code(409);
					wiki_edit_conflict($post_data, $post_original, $latest);
					exit(); }

				$body = strlen($post_data['body'])
					? lf($post_data['body'])
					: null;

				$DB->execParams('UPDATE _wiki_versioned
					SET _is_latest = 0
					WHERE _is_latest = 1 AND slug = ? ', [ $slug ] );

				$DB->execParams('
					INSERT INTO _wiki_versioned (slug, body, _body_sha1,
						mtime, _is_latest)
					SELECT ?, ?, ?,
						strftime(\'%s\', \'now\'), 1',
					[ $slug, $body, sha1($body) ] );

				$St = $DB->prepare('INSERT INTO _wiki_slug_use (from_slug, to_slug) VALUES (?, ?)');
				foreach (wiki_post_body_to_slugs($body .'') as $v)
					$St->execute([ $slug, $v ]);

			$ctx = array_merge($ctx??[], array_subscripts($post_ctx, 'local_page'));

			$DB->commit();
			$Li = new Slinky('/');
			$Li = $Li->with(compact('set', 'slug', 'ctx'))
				->with(array_subscripts($post_meta, 'selectionStart', 'selectionEnd'));
			if ($action === 'save-see')
				$Li = $Li->withFragment('article-saved');
			else {
				$Li = $Li->withFragment('article-saved-continue')
					->with(compact('service', 'form')); }

			$Li->redirectSeeOther(); }
