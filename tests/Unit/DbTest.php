<?php

namespace DfTools\SlimOrm\Tests\Unit;

use DfTools\SlimOrm\DB;
use DfTools\SlimOrm\SlimOrmException;
use DfTools\SlimOrm\Tests\Lib\AccessibilitySwapper;
use DfTools\SlimOrm\QueryBuilder;
use DfTools\SlimOrm\Tests\Lib\BaseTestClass;
use DfTools\SlimOrm\Tests\Lib\QueryBuilderCallableTrait;

class DbTest extends BaseTestClass
{
    use QueryBuilderCallableTrait;

    /** @test */
    public function the_db_constructor_is_private()
    {
        try {
            new DB;
        } catch (\Throwable $th) {
            $this->assertStringContainsString(
                'Call to private DfTools\SlimOrm\DB::__construct()', 
                $th->getMessage()
            );
        }
    }

    /** @test */
    public function the_db_can_be_initialized_only_once()
    {
        try {
            DB::getInstance();
        } catch (\Throwable $th) {
            $this->assertInstanceOf(SlimOrmException::class, $th);
            
            $this->assertSame(
                $th->getMessage(),
                'The DB has not been initialized yet.'
            );
        }

        $instance1 = DB::init($this->getPdoInstance());
        $instance2 = DB::init($this->getPdoInstance());
        $instance3 = DB::getInstance();

        $this->assertSame($instance1, $instance2);
        $this->assertSame($instance2, $instance3);
    }

    /** @test */
    public function some_methods_of_QueryBuilder_can_be_called_from_the_db_instance()
    {
        $db = DB::getInstance();

        foreach ($this->getCallableMethods() as $method => $testData) {
            if (isset($testData['skip'])) continue;

            $params = $testData['params'] ?? [];

            if (isset($testData['allowed_in_model']) or isset($testData['exception'])) {
                try {
                    $db->{$method}(...$params);
                } catch (\Throwable $ex) {
                    $this->assertTrue($ex instanceof SlimOrmException);
                }
            } else {
                $res = $db->{$method}(...$params);
                $this->assertTrue($res instanceof QueryBuilder);

                $res = DB::{$method}(...$params);
                $this->assertTrue($res instanceof QueryBuilder);
            }
        }
    }

    /** @test */
    public function test_query_and_execStatement()
    {
        $this->refreshDb();

        $actual = $this->db()->query("SELECT * FROM `users`");
        $this->assertEquals([], $actual);

        $this->db()->execStatement("INSERT INTO users (`name`, `age`) VALUES ('User A', 10)");
        $this->db()->execStatement("INSERT INTO users (`name`, `age`) VALUES ('User B', 20)");
        $this->db()->execStatement("INSERT INTO users (`name`, `age`) VALUES ('User C', 30)");

        $actual = $this->db()->query("SELECT * FROM `users` ORDER BY `id`");

        $this->assertEquals([
            (object)['id' => 1, 'name' => 'User A', 'age' => 10],
            (object)['id' => 2, 'name' => 'User B', 'age' => 20],
            (object)['id' => 3, 'name' => 'User C', 'age' => 30],
        ], $actual);
    }

    /** @test */
    public function some_methods_of_the_query_builder_are_not_callable_from_the_db_obj()
    {
        $this->db(); // calling this method just to make sure the DB object is initialized

        $notCallableMEthods = array_merge($this->callableMethodsFromModel, $this->notExtCallableMethods);

        foreach ($notCallableMEthods as $method) {
            try {
                DB::$method();
            } catch (\Throwable $th) {
                $this->assertInstanceOf(SlimOrmException::class, $th);
                $this->assertEquals(
                    'Call to undefined static method DB::' . $method . '()',
                    $th->getMessage()
                );
            }
        }

        $db = DB::getInstance();

        foreach ($notCallableMEthods as $method) {
            try {
                $db->$method();
            } catch (\Throwable $th) {
                $this->assertInstanceOf(SlimOrmException::class, $th);
                $this->assertEquals(
                    'Call to undefined method DB::' . $method . '()',
                    $th->getMessage()
                );
            }
        }
    }

    /** @test */
    public function some_functions_of_query_builder_are_callable_from_the_db_object()
    {
        $db = $this->db(); // calling this method just to make sure the DB object is initialized

        foreach (get_class_methods(QueryBuilder::class) as $method) {
            if (in_array($method, array_merge($this->callableMethodsFromModel, $this->notExtCallableMethods))) continue;
            if ($method == '__construct') continue;

            $params = $this->getMethodParams($method);

            $query = DB::$method(...$params);
            $this->assertInstanceOf(QueryBuilder::class, $query);

            $query = $db->$method(...$params);
            $this->assertInstanceOf(QueryBuilder::class, $query);
        }
    }

    /** @test */
    public function db_query_test()
    {
        $db = $this->db(); // calling this method just to make sure the DB object is initialized

        $this->refreshDb();
        
        $users = $this->createFakeUsers(5);

        $expected = [
            (object)[ 'id' => 4, 'name' => 'Test 4', 'age' => 40 ],
            (object)[ 'id' => 3, 'name' => 'Test 3', 'age' => 30 ]
        ];

        $actual = DB::table('users')
            ->where('name', '=', 'Test 2')
            ->orWhere('age', '=', 30)
            ->orWhere(function($subQuery) {
                $subQuery->where('name', '=', 'Test 4')->where('age', '=', 40);
            })
            ->orderBy('id', 'DESC')
            ->limit(2)
            ->get();
        
        $this->assertEquals($expected, $actual);

        $actual = $db->table('users')
            ->orderBy('id')
            ->get();
        
        $this->assertEquals($users, $actual);
    }

}
