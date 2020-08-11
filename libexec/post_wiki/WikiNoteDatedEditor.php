<?php

		if (($action === 'save-edit') || ($action === 'save-see')) {
			$DB->beginTransaction();
				$ndrcd = $DB->queryFetch('SELECT * FROM post_wiki_note_dated WHERE slug= ? AND date = ?', [ $slug, $date ]);

				$date = $post_data['date'];

				if (empty($ndrcd))
					$DB->execParams('INSERT INTO post_wiki_note_dated (slug, date, body) VALUES (?, ?, ?)',
						[ $slug, $date, lf($post_data['body']) ] );
				else if (empty($post_data['body']))
					$DB->execParams('
						DELETE FROM post_wiki_note_dated WHERE slug = ? AND date = ?',
						[ $slug, $date ] );
				else {
					$latest = $ndrcd;
					if (lf($post_original['body']) !== lf($latest['body'])) {
						header_response_code(409);
						wiki_nd_edit_conflict($post_data, $post_original, $latest);
						exit(); }
					$DB->execParams('UPDATE post_wiki_note_dated SET body = ? WHERE slug = ? AND date = ?',
						[ lf($post_data['body']), $slug, $date ]);
				}

				$DB->execParams('UPDATE post_wiki_note_dated SET _mtime = strftime(\'%s\', \'now\') WHERE slug = ? AND date = ?', [$slug, $date]);

			$DB->commit();

			header_response_code(303);
			if ($action === 'save-see')
				die(header('Location: ?set=post_wiki&slug=' .U($slug) .'&selectionStart=' .U($post_meta['selectionStart']) .'&selectionEnd=' .U($post_meta['selectionEnd']) .'#wnd-saved'));
			else
				die('unsupported action');
		}
