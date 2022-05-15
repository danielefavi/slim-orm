<?php

require_once __DIR__ . '/../bootstrap.php';

use DfTools\SlimOrm\DB;

function db(): DB
{
    if (! DB::getInstance()) {
        if (! file_exists(__DIR__ . '/sqlite.db')) {
            file_put_contents(__DIR__ . '/sqlite.db', '');
        }
    
        $pdo = new \PDO('sqlite:' . __DIR__ . '/sqlite.db');
        
        return DB::init($pdo);
    }

    return DB::getInstance();
}

db()->query('
    CREATE TABLE IF NOT EXISTS "users" (
        "id"	INTEGER,
        "name"	TEXT,
        "age"	INTEGER,
        PRIMARY KEY("id" AUTOINCREMENT)
    );
');