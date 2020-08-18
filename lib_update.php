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
$DB->exec(<<<'EOS'
CREATE TABLE IF NOT EXISTS post_wiki (
	post_id INTEGER PRIMARY KEY,
	uuid TEXT NOT NULL,
	_url_slug TEXT NOT NULL,
	title TEXT,
	body TEXT, _mtime INTEGER,
	UNIQUE(_url_slug),
	UNIQUE(uuid)
);
CREATE TABLE IF NOT EXISTS _wiki_slug_use (
	post_id INTEGER,
	_url_slug TEXT,
	PRIMARY KEY (post_id, _url_slug),
	FOREIGN KEY (post_id) REFERENCES post_wiki(post_id)
);
EOS);
$DB->exec(<<<'EOS'
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
$DB->exec(<<<'EOS'
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
		$DB->beginTransaction();
$DB->exec(<<<'EOS'
ALTER TABLE post_wiki
ADD "_body_sha1" TEXT;
EOS);
		wiki_recalc_all_body_sha1();
		update_Db_version($DB, 3);
		$DB->commit();
	case 3:
		$DB->beginTransaction();
$DB->exec(<<<'EOS'
CREATE TABLE _post_wiki_sync (
	slug TEXT NOT NULL,
	connection TEXT NOT NULL,
	body_local TEXT NOT NULL,
	body_remote TEXT NOT NULL,
	timestamp_local INTEGER NOT NULL,
	timestamp_remote INTEGER NOT NULL,
	sha1_local TEXT NOT NULL,
	sha1_remote TEXT NOT NULL,
	PRIMARY KEY(slug, connection)
);
EOS);
		update_db_version($DB, 4);
		$DB->commit();
	case 4:
		/* the current version */; }
}

function update_the_config()
{
	$DB = config_db_pdo();

	switch (update_db_version($DB)) {
	default:
		throw new Exception(sprintf('unsupported DB version'));
	case 0:
		$DB->beginTransaction();
$DB->exec(<<<'EOS'
CREATE TABLE config (
	key TEXT NOT NULL PRIMARY KEY,
	value TEXT DEFAULT NULL
);
EOS);
$DB->exec(<<<'EOS'
INSERT INTO config VALUES('i18n.lang','en');
INSERT INTO config VALUES('federation.connections','');
EOS);
		update_db_version($DB, 1);
		$DB->commit();
	case 1:
		/* the current version */; }
}
