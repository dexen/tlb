<?php

class DB extends PDO
{
	function __construct(string $relative_pathname)
	{
		$abs_pathname = INSTALL_DIR .'/' .$relative_pathname;
		parent::__construct(sprintf('sqlite:%s', $abs_pathname), null, null, [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		]);
		$this->exec('PRAGMA foreign_keys = ON');
	}

	function queryFetch(string $query, array $params = null)
	{
		if (empty($params))
			$St = $this->query($query);
		else {
			$St = $this->prepare($query);
			$St->execute($params); }
		switch (count($a = $St->fetchAll())) {
		case 1:
			return array_shift($a);
		case 0:
			return null;
		default:
			throw new Exception('multiple records, expected at most one'); }
	}

	function queryFetchAll(string $query, array $params = null) : array
	{
		if (empty($params))
			$St = $this->query($query);
		else {
			$St = $this->prepare($query);
			$St->execute($params); }
		return $St->fetchAll();
	}
}
