<?php

	$DB = db_pdo();

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
				else
					$DB->execParams('UPDATE post_wiki SET body = ? WHERE _url_slug = ?',
						[ lf($post_data['body']), $slug ]);

				$DB->execParams('UPDATE post_wiki SET _mtime = strftime(\'%s\', \'now\') WHERE _url_slug = ?', [$slug]);

				$rcd = $DB->queryFetch('SELECT post_id, body FROM post_wiki WHERE _url_slug = ?', [ $slug ]);
				if ($rcd) {
					$St = $DB->prepare('INSERT INTO _wiki_slug_use (post_id, _url_slug) VALUES (?, ?)');
					foreach (wiki_post_to_linked_slugs($rcd) as $v)
						$St->execute([ $rcd['post_id'], $v ]); }
			$DB->commit();

			if ($action === 'save-see')
				die(header('Location: ?set=post_wiki&slug=' .U($slug)));
			else
				die(header('Location: ?set=post_wiki&slug=' .U($slug) .'&service=' .U($service) .'&form=' .U($form)));
		}
