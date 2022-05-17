<?php

namespace DfTools\SlimOrm\Tests\Lib;

use PHPUnit\Framework\TestCase;
use DfTools\SlimOrm\DB;
use DfTools\SlimOrm\QueryBuilder;

class BaseTestClass extends TestCase
{

    /**
     * Return the PDO instance.
     *
     * @return \PDO
     */
    protected function getPdoInstance(): \PDO
    {
        return new \PDO('sqlite:' . __DIR__ . '/../dbtest.db');
    }

    /**
     * Return safely the DB instance: if the DB is not initialized then is going
     * to initialize it.
     *
     * @return DB
     */
    protected function db(): DB
    {
        return DB::init($this->getPdoInstance());
    }

    /**
     * Get the callable methods of the QueryBuilder class that can be invoked
     * from the DB class and Model class. Before returning the array it checks
     * that the list of methods are up to date.
     *
     * @return array
     */
    protected function getCallableMethods(): array
    {
        $methods = get_class_methods(QueryBuilder::class);
        sort($methods);

        $callableMethods = [
            '__construct' => [
                'skip' => true,
             ],
            'methodIsCallable' => [
                'params' => ['methodName'],
                'exception' => true,
            ],
            'select' => [
                'params' => ['id'] 
            ],
            'table' => [
                'params' => ['notes'] 
            ],
            'join' => [
                'params' => ['posts', 'posts.id', '=', 'comments.post_id'] 
            ],
            'leftJoin' => [
                'params' => ['posts', 'posts.id', '=', 'comments.post_id'] 
            ],
            'rightJoin' => [
                'params' => ['posts', 'posts.id', '=', 'comments.post_id'] 
            ],
            'whereRaw' => [
                'params' => ['SELECT * FROM notes' , []] 
            ],
            'where' => [
                'params' => ['name', '=', 'Mark'] 
            ],
            'orWhere' => [
                'params' => ['name', '=', 'Mark'] 
            ],
            'whereNull' => [
                'params' => ['name'] 
            ],
            'orWhereNull' => [
                'params' => ['name'] 
            ],
            'limit' => [
                'params' => [10] 
            ],
            'offset' => [
                'params' => [10] 
            ],
            'orderBy' => [
                'params' => ['id'] 
            ],
            'orderByAsc' => [
                'params' => ['id'] 
            ],
            'orderByDesc' => [
                'params' => ['id'] 
            ],
            'groupBy' => [
                'params' => ['name'] 
            ],

            'buildSelectQuery' => [
                'allowed_in_model' => true
            ],
            'update' => [
                'params' => [['name' => 'Mark']],
                'allowed_in_model' => true
            ],
            'buildUpdateQuery' => [
                'params' => [['name' => 'Mark']],
                'allowed_in_model' => true
            ],
            'insert' => [
                'params' => [['name' => 'Mark']],
                'allowed_in_model' => true
            ],
            'buildInsertQuery' => [
                'params' => [['name' => 'Mark']],
                'allowed_in_model' => true
            ],
            'delete' => [
                'allowed_in_model' => true
            ],
            'buildDeleteQuery' => [
                'allowed_in_model' => true
            ],
            'get' => [
                'allowed_in_model' => true
            ],
            'first' => [
                'allowed_in_model' => true
            ],
            'count' => [
                'allowed_in_model' => true
            ],
            'max' => [
                'params' => ['age'],
                'allowed_in_model' => true
            ],
            'min' => [
                'params' => ['age'],
                'allowed_in_model' => true
            ],
            'paginate' => [
                'allowed_in_model' => true
            ],
        ];

        $methodsToTest = array_keys($callableMethods);
        sort($methodsToTest);

        $this->assertEquals($methods, $methodsToTest);

        return $callableMethods;
    }

    /**
     * Refresh the database: if the users table does not exist then it creates it;
     * if the users table exists then it truncates it.
     *
     * @return void
     */
    protected function refreshDb()
    {
        if (! $this->usersTableExists()) {
            $this->createUserTable();
        } else {
            $this->truncateUsersTable();
        }
    }

    /**
     * Execute the query for creating the users DB table.
     *
     * @return void
     */
    protected function createUserTable()
    {
        $this->db()->query('
            CREATE TABLE "users" (
                "id"	INTEGER,
                "name"	TEXT,
                "age"	INTEGER,
                PRIMARY KEY("id" AUTOINCREMENT)
            );
        ');
    }

    /**
     * Execute the queries for truncating the users table.
     *
     * @return void
     */
    protected function truncateUsersTable()
    {   
        $this->db()->execStatement("DELETE FROM users;");
        $this->db()->execStatement("DELETE FROM SQLITE_SEQUENCE WHERE name='users';");
    }

    /**
     * Check if the users table exists.
     *
     * @return boolean
     */
    protected function usersTableExists(): bool
    {
        $res = $this->db()->query("
            SELECT name FROM sqlite_master WHERE type='table' AND name='users';
        ");

        return !empty($res);
    }

    /**
     * Create fake users in the database.
     *
     * @param integer $num
     * @return array
     */
    protected function createFakeUsers(int $num=0): array
    {
        $users = $this->generateFakeUsersData($num);

        foreach ($users as $user) {
            $this->db()->execStatement("INSERT INTO users (`id`, `name`, `age`) VALUES (".$user->id.", '".$user->name."', ".$user->age.")");
        }

        return $users;
    }

    /**
     * Generate user data for testing without adding anything to the database.
     *
     * @param integer $num
     * @return array
     */
    protected function generateFakeUsersData(int $num=0): array
    {
        $users = [];

        for ($i=1; $i <= $num; $i++) { 
            $users[] = (object)[
                'id' => $i, 
                'name' => "Test $i", 
                'age' => $i * 10
            ];
        }

        return $users;
    }

    /**
     * Create fake users in the database.
     *
     * @param integer $num
     * @return array
     */
    protected function createFakeUsersNoIds(int $num=0): array
    {
        $users = [];

        for ($i=1; $i <= $num; $i++) { 
            $users[] = (object)[ 'name' => "Test $i", 'age' => $i * 10 ];
        }

        foreach ($users as $user) {
            $this->db()->execStatement("INSERT INTO users (`name`, `age`) VALUES ('".$user->name."', ".$user->age.")");
        }

        return $users;
    }

}