<?php

require '../init.php';

echo json_encode(db_pdo()->queryFetchAll('SELECT slug, mtime, body, _is_latest FROM _wiki_versioned'));
