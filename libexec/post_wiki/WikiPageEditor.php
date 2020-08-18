<?php

		if (($action === 'save-edit') || ($action === 'save-see')) {
			$DB->beginTransaction();
				$DB->execParams('
					DELETE FROM _wiki_slug_use
					WHERE post_id IN (SELECT post_id FROM post_wiki WHERE _url_slug = ?)', [ $slug ]);
				if (empty($rcd))
					$DB->execParams('INSERT INTO post_wiki (body, _url_slug, uuid) VALUES (?, ?, ?)',
						[ lf($post_data['body']), $slug, Uuid::generateUuidV4() ] );
				else if (empty($post_data['body']))
					$DB->execParams('
						DELETE FROM post_wiki WHERE _url_slug = ? ',
						[ $slug ] );
				else {
					$latest = $DB->queryFetch('SELECT * FROM post_wiki WHERE _url_slug = ?', [$slug]);
#if (preg_match('/bbb/', $post_data['body']))td(compact('post_original', 'latest'));
					if (lf($post_original['body']) !== lf($latest['body'])) {
						header_response_code(409);
						wiki_edit_conflict($post_data, $post_original, $latest);
						exit(); }
					$DB->execParams('UPDATE post_wiki SET body = ?, _body_sha1 = NULL WHERE _url_slug = ?',
						[ lf($post_data['body']), $slug ]);
				}

				$DB->execParams('UPDATE post_wiki SET _mtime = strftime(\'%s\', \'now\') WHERE _url_slug = ?', [$slug]);

				$rcd = $DB->queryFetch('SELECT post_id, body FROM post_wiki WHERE _url_slug = ?', [ $slug ]);
				if ($rcd) {
					$St = $DB->prepare('INSERT INTO _wiki_slug_use (post_id, _url_slug) VALUES (?, ?)');
					foreach (wiki_post_body_to_slugs($rcd['body']) as $v)
						$St->execute([ $rcd['post_id'], $v ]); }

			wiki_recalc_all_body_sha1();

			$DB->commit();

			header_response_code(303);
			if ($action === 'save-see')
				die(header('Location: ?set=post_wiki&slug=' .U($slug) .'&selectionStart=' .U($post_meta['selectionStart']) .'&selectionEnd=' .U($post_meta['selectionEnd']) .'#article-saved'));
			else
				die(header('Location: ?set=post_wiki&slug=' .U($slug) .'&service=' .U($service) .'&form=' .U($form) .'&selectionStart=' .U($post_meta['selectionStart']) .'&selectionEnd=' .U($post_meta['selectionEnd']) .'#article-saved-continue'));
		}
