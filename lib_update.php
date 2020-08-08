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
		/* the current version */; }
}
