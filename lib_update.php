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
		/* the current version */; }
}
