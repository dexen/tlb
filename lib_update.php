<?php

function update_db_version(DB $DB, int $user_version = null) : int
{
	if ($user_version !== null)
		$DB->pragma('user_version', $user_version);
	return $DB->pragma('user_version')[0];
}

function update_the_db()
{
	$DB = db_pdo();

	switch (update_db_version($DB)) {
	default:
		throw new Exception(sprintf('unsupported DB version'));
	case 0:
		$DB->beginTransaction();
$DB->exec(<<<EOS
CREATE TABLE numbered_note (
	slug TEXT NOT NULL,
	number INTEGER NOT NULL,
	body TEXT,
	PRIMARY KEY (slug, number)
);
EOS);
		update_db_version($DB, 1);
		$DB->commit();
	case 1:
		$DB->beginTransaction();
$DB->exec(<<<EOS
CREATE TABLE post_wiki_note_dated (
	slug TEXT NOT NULL,
	date TEXT NOT NULL,
	body TEXT,
	_mtime INTEGER,
	PRIMARY KEY (slug, date)
);
EOS);
		update_db_version($DB, 2);
		$DB->commit();
	case 2:
		/* the current version */; }
}
