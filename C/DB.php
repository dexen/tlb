<?php

class DB extends PDO
{
	function __construct(string $relative_pathname)
	{
		$abs_pathname = INSTALL_DIR .'/' .$relative_pathname;
		parent::__construct(sprintf('sqlite:%s', $abs_pathname), null, null, [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		]);
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
