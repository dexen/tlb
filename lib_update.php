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
		$DB->beginTransaction();
$DB->exec('DROP TABLE IF EXISTS _post_wiki_sync;');
$DB->exec(<<<'EOS'
CREATE TABLE _post_wiki_sync (
	slug TEXT NOT NULL,
	connection TEXT NOT NULL,
	body_local TEXT DEFAULT NULL,
	body_remote TEXT DEFAULT NULL,
	timestamp_local INTEGER DEFAULT NULL,
	timestamp_remote INTEGER DEFAULT NULL,
	sha1_local TEXT DEFAULT NULL,
	sha1_remote TEXT DEFAULT NULL,
	PRIMARY KEY(slug, connection)
);
EOS);
		update_db_version($DB, 5);
		$DB->commit();
	case 5:
		$DB->beginTransaction();
		$DB->exec(<<<'EOS'
CREATE TABLE _wiki_versioned (
	slug TEXT NOT NULL,
	mtime INTEGER NOT NULL,
	_is_latest INT NOT NULL,
	body TEXT DEFAULT NULL,
	_body_sha1 TEXT NOT NULL,
	PRIMARY KEY(slug, mtime)
);
EOS);
		$DB->exec(<<<'EOS'
CREATE UNIQUE INDEX wiki_latest ON _wiki_versioned(slug) WHERE _is_latest = 1;
EOS);
		$DB->exec(<<<'EOS'
INSERT INTO _wiki_versioned (slug, mtime, _is_latest, body, _body_sha1)
	SELECT _url_slug, COALESCE(_mtime, strftime('%s', 'now')), 1, body, _body_sha1 FROM post_wiki;
EOS);
		$DB->exec('ALTER TABLE post_wiki RENAME TO old_post_wiki;');
		$DB->exec(<<<'EOS'
CREATE VIEW wiki AS
SELECT *
FROM _wiki_versioned
WHERE _is_latest = 1 AND body IS NOT NULL;
EOS);
		$DB->exec(<<<'EOS'
CREATE VIEW post_wiki AS
SELECT NULL AS post_id, NULL AS uuid, slug AS _url_slug, NULL AS title, body, mtime AS _mtime, _body_sha1
FROM wiki;
EOS);
		update_db_version($DB, 6);
		$DB->commit();
	case 6:
		$DB->beginTransaction();
		$DB->exec('ALTER TABLE _wiki_slug_use RENAME TO _old_wiki_slug_use;');
		$DB->exec(<<<'EOS'
CREATE TABLE _wiki_slug_use (
	from_slug TEXT NOT NULL,
	to_slug TEXT NOT NULL,
	PRIMARY KEY(from_slug, to_slug)
);
EOS);
		update_db_version($DB, 7);
		$DB->commit();
		wiki_maintenance_rebuild_slug_reverse_index();
	case 7:
		$DB->beginTransaction();
		$DB->exec(<<<'EOS'
DROP TABLE IF EXISTS _wiki_versioned_remote;

CREATE TABLE _wiki_versioned_remote (
	connection TEXT NOT NULL,
	slug TEXT NOT NULL,
	mtime INTEGER NOT NULL,
	_is_latest INT NOT NULL,
	body TEXT DEFAULT NULL,
	_body_sha1 TEXT NOT NULL,
	PRIMARY KEY(connection, slug, mtime)
);
EOS);
		$DB->exec(<<<'EOS'
CREATE UNIQUE INDEX _wiki_versioned_remote_latest ON _wiki_versioned_remote(connection, slug) WHERE _is_latest = 1;
EOS);
		update_db_version($DB, 8);
		$DB->commit();
	case 8:
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
