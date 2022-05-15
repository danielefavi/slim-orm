<?php

namespace DfTools\SlimOrm\Tests\Unit;

use DfTools\SlimOrm\DB;
use DfTools\SlimOrm\SlimOrmException;
use DfTools\SlimOrm\QueryBuilder;
use DfTools\SlimOrm\Tests\Lib\AccessibilitySwapper;
use DfTools\SlimOrm\Tests\Lib\BaseTestClass;
use DfTools\SlimOrm\Tests\Lib\UsersModel;
use DfTools\SlimOrm\Tests\Lib\QueryBuilderCallableTrait;

class ModelCallableFncTest extends BaseTestClass
{
    use QueryBuilderCallableTrait;

    /* ********************************************************************** */
    /* *******************          UPDATE TESTS          ******************* */
    /* ********************************************************************** */

    /** @test */
    public function mfc_base_query_update_test()
    {
        $this->db();

        $query = UsersModel::query();

        $this->assertEquals(
            'UPDATE `users` SET',
            $this->buildUpdateAndSanitize($query, [])
        );
        $this->assertEqualsCanonicalizing([], (new AccessibilitySwapper($query))->getPropertyValue('data'));

        $this->assertEquals(
            'UPDATE `users` SET `f1` = :f1 , `f2` = :f2',
            $this->buildUpdateAndSanitize($query, [ 'f1' => 'v1', 'f2' => 'v2' ])
        );
        $this->assertEqualsCanonicalizing([], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function mfc_query_update_test()
    {
        $query = UsersModel::where('a', '>=', 'aaa')
            ->orWhere(function($subQuery) {
                $subQuery->where('b', '<=', 'bbb')->where('c', '!=', 'ccc');
            });
        
        $this->assertEquals(
            'UPDATE `users` SET WHERE `a` >= :1_sql_data OR ( `b` <= :2_1_sql_data_sub_q AND `c` != :2_2_sql_data_sub_q )',
            $this->buildUpdateAndSanitize($query, [])
        );

        $this->assertEqualsCanonicalizing([ 
            '1_sql_data'            => 'aaa',
            '2_1_sql_data_sub_q'    => 'bbb',
            '2_2_sql_data_sub_q'    => 'ccc',
        ], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function mfc_update_test()
    {
        $this->refreshDb();

        $this->db()->execStatement("INSERT INTO users (`name`, `age`) VALUES ('Mark', 20)");
        $this->db()->execStatement("INSERT INTO users (`name`, `age`) VALUES ('John', 30)");
        $this->db()->execStatement("INSERT INTO users (`name`, `age`) VALUES ('Mary', 40)");

        $this->assertEquals(0, $this->db()->table('users')->where('age', '<=', 10)->count());
        $this->assertEquals(0, $this->db()->table('users')->where('name', '=', 'Test')->count());

        UsersModel::where('age', '>=', 30)
            ->update([
                'name' => 'Test',
                'age' => 10
            ]);

        $this->assertEquals(2, $this->db()->table('users')->where('age', '<=', 10)->count());
        $this->assertEquals(2, $this->db()->table('users')->where('name', '=', 'Test')->count());
        $this->assertEquals(1, $this->db()->table('users')->where('name', '=', 'Mark')->count());
    }

    /* ********************************************************************** */
    /* *******************          INSERT TESTS          ******************* */
    /* ********************************************************************** */

    /** @test */
    public function mfc_base_query_insert_test()
    {
        $query = UsersModel::query();
        
        $this->assertEquals(
            'INSERT INTO `users` () VALUES ()',
            $this->buildInsertAndSanitize($query, [])
        );
        $this->assertEqualsCanonicalizing([], (new AccessibilitySwapper($query))->getPropertyValue('data'));

        $this->assertEquals(
            'INSERT INTO `users` ( `f1` , `f2` ) VALUES ( :f1 , :f2 )',
            $this->buildInsertAndSanitize($query, [ 'f1' => 'v1', 'f2' => 'v2' ])
        );
        $this->assertEqualsCanonicalizing([], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function mfc_insert_test()
    {
        $this->refreshDb();

        $this->assertEquals(0, $this->db()->table('users')->count());

        $id = UsersModel::insert([
                'name' => 'Test 1',
                'age' => 99
            ]);
        
        $this->assertEquals(1, $id);
        $this->assertEquals(1, $this->db()->table('users')->count());

        $id = UsersModel::insert([
                'name' => 'Test 2',
                'age' => 88
            ]);
        $this->assertEquals(2, $id);

        $this->assertEquals(2, $this->db()->table('users')->count());
        $this->assertEquals(1, $this->db()->table('users')->where('name', '=', 'Test 1')->count());
        $this->assertEquals(1, $this->db()->table('users')->where('name', '=', 'Test 2')->count());
    }

    /* ********************************************************************** */
    /* *******************          DELETE TESTS          ******************* */
    /* ********************************************************************** */

    /** @test */
    public function mfc_base_query_delete_test()
    {
        $query = UsersModel::query();
        
        $this->assertEquals(
            'DELETE FROM `users`',
            $this->buildDeleteAndSanitize($query, [])
        );
        $this->assertEqualsCanonicalizing([], (new AccessibilitySwapper($query))->getPropertyValue('data'));

        $query = UsersModel::where('a', '=', 'b');
        
        $this->assertEquals(
            'DELETE FROM `users` WHERE `a` = :1_sql_data',
            $this->buildDeleteAndSanitize($query, [])
        );
        $this->assertEqualsCanonicalizing([
            '1_sql_data' => 'b'
        ], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function mfc_delete_test()
    {
        $this->refreshDb();

        UsersModel::insert([ 'name' => 'Test 1', 'age' => 10 ]);
        UsersModel::insert([ 'name' => 'Test 2', 'age' => 20 ]);
        UsersModel::insert([ 'name' => 'Test 3', 'age' => 30 ]);

        $this->assertEquals(3, $this->db()->table('users')->count());

        UsersModel::where('age', '>', 15)->delete();
        
        $this->assertEquals(1, $this->db()->table('users')->count());
        $this->assertEquals(0, $this->db()->table('users')->where('age', '>=', 15)->count());

        UsersModel::insert([ 'name' => 'Test 4', 'age' => 40 ]);
        $this->assertEquals(2, $this->db()->table('users')->count());

        UsersModel::delete();

        $this->assertEquals(0, $this->db()->table('users')->count());
    }

    /* ********************************************************************** */
    /* **********************        GET TESTS        *********************** */
    /* ********************************************************************** */

    /** @test */
    public function mfc_get_test_with_empty_table()
    {
        $this->refreshDb();

        $actual = UsersModel::get();
        $expected = $this->db()->query("SELECT * FROM `users`");

        $this->assertEquals($expected, $actual);
    }

    /** @test */
    public function mfc_get_test_get_all_records()
    {
        $this->refreshDb();

        $expectedUsers = [
            UsersModel::initStatic([ 'id' => 1, 'name' => 'Test 1', 'age' => 10 ]),
            UsersModel::initStatic([ 'id' => 2, 'name' => 'Test 2', 'age' => 20 ]),
            UsersModel::initStatic([ 'id' => 3, 'name' => 'Test 3', 'age' => 30 ])
        ];

        foreach ($expectedUsers as $user) {
            UsersModel::insert($user->toArray());
        }

        $actual = UsersModel::get();

        $this->assertEqualsCanonicalizing($expectedUsers, $actual);
    }

    /** @test */
    public function mfc_get_test_where_query()
    {
        $this->refreshDb();
        
        $users = $this->createFakeUsers(5);

        $expectedUsers = [
            UsersModel::initStatic([ 'id' => 2, 'name' => 'Test 2', 'age' => 20 ]),
            UsersModel::initStatic([ 'id' => 3, 'name' => 'Test 3', 'age' => 30 ])
        ];

        $actual = UsersModel::where('age', '>', 15)->where('age', '<', '35')->get();

        $this->assertEqualsCanonicalizing($expectedUsers, $actual);
    }

    /** @test */
    public function mfc_get_generic_test()
    {
        $this->refreshDb();
        
        $users = $this->createFakeUsers(5);

        $expected = [
            UsersModel::initStatic([ 'id' => 4, 'name' => 'Test 4', 'age' => 40 ]),
            UsersModel::initStatic([ 'id' => 3, 'name' => 'Test 3', 'age' => 30 ])
        ];

        $actual = UsersModel::where('name', '=', 'Test 2')
            ->orWhere('age', '=', 30)
            ->orWhere(function($subQuery) {
                $subQuery->where('name', '=', 'Test 4')->where('age', '=', 40);
            })
            ->orderBy('id', 'DESC')
            ->limit(2)
            ->get();
        
        $this->assertEquals($expected, $actual);
    }

    /* ********************************************************************** */
    /* *******************        FIRST TESTS        ************************ */
    /* ********************************************************************** */

    /** @test */
    public function mfc_first_test_with_empty_table()
    {
        $this->refreshDb();

        $actual = UsersModel::first();

        $this->assertEquals(null, $actual);
    }

    /** @test */
    public function mfc_first_generic_test()
    {
        $this->refreshDb();
        
        $users = $this->createFakeUsers(5);

        $expected = [ 'id' => 4, 'name' => 'Test 4', 'age' => 40 ];

        $actual = UsersModel::where('name', '=', 'Test 2')
            ->orWhere('age', '=', 30)
            ->orWhere(function($subQuery) {
                $subQuery->where('name', '=', 'Test 4')->where('age', '=', 40);
            })
            ->orderBy('id', 'DESC')
            ->first();
        
        $this->assertEquals($expected, $actual->toArray());
    }

    /* ********************************************************************** */
    /* *******************        COUNT TESTS        ************************ */
    /* ********************************************************************** */

    /** @test */
    public function mfc_count_test_with_empty_table()
    {
        $this->refreshDb();

        $actual = UsersModel::count();

        $this->assertEquals(0, $actual);
    }

    /** @test */
    public function mfc_count_generic_test()
    {
        $this->refreshDb();
        
        $users = $this->createFakeUsers(5);

        $actual = UsersModel::where('name', '=', 'Test 2')
            ->orWhere('age', '=', 30)
            ->orWhere(function($subQuery) {
                $subQuery->where('name', '=', 'Test 4')->where('age', '=', 40);
            })
            ->count();
        
        $this->assertEquals(3, $actual);
    }

    /* ********************************************************************** */
    /* *******************         MIN TESTS         ************************ */
    /* ********************************************************************** */

    /** @test */
    public function mfc_min_test_with_empty_table()
    {
        $this->refreshDb();

        $actual = UsersModel::min('id');

        $this->assertEquals(null, $actual);
    }

    /** @test */
    public function mfc_min_generic_test()
    {
        $this->refreshDb();
        
        $users = $this->createFakeUsers(5);

        $actual = UsersModel::where('name', '=', 'Test 2')
            ->orWhere('age', '=', 30)
            ->orWhere(function($subQuery) {
                $subQuery->where('name', '=', 'Test 4')->where('age', '=', 40);
            })
            ->min('id');
        
        $this->assertEquals(2, $actual);
    }

    /* ********************************************************************** */
    /* *******************         MAX TESTS         ************************ */
    /* ********************************************************************** */

    /** @test */
    public function mfc_max_test_with_empty_table()
    {
        $this->refreshDb();

        $actual = UsersModel::max('id');

        $this->assertEquals(null, $actual);
    }
    
    /** @test */
    public function mfc_max_generic_test()
    {
        $this->refreshDb();
        
        $users = $this->createFakeUsers(5);

        $actual = UsersModel::where('name', '=', 'Test 2')
            ->orWhere('age', '=', 30)
            ->orWhere(function($subQuery) {
                $subQuery->where('name', '=', 'Test 4')->where('age', '=', 40);
            })
            ->max('id');
        
        $this->assertEquals(4, $actual);
    }

    /* ********************************************************************** */
    /* ****************         PAGINATE TESTS         ********************** */
    /* ********************************************************************** */

    /** @test */
    public function mfc_paginate_test_with_empty_table()
    {
        $this->refreshDb();

        $actual = UsersModel::paginate();

        $this->assertEquals([
            'total' => 0,
            'per_page' => 20,
            'current_page' => 0,
            'last_page' => 0,
            'from' => 0,
            'to' => 0,
            'data' => [],
        ], $actual);
    }

    /** @test */
    public function mfc_paginate_generic_test()
    {
        $this->refreshDb();
        
        $users = $this->createFakeUsers(100);

        $this->paginationTest_firstPage();
        $this->paginationTest_thirdPage();
    }

    private function paginationTest_firstPage()
    {
        $actual = UsersModel::where('age', '>=', '50')
            ->where('age', '<=', '450')
            ->orderByDesc('id')
            ->paginate(5);
        
        $expectedData = [
            UsersModel::initStatic(['id' => 45, 'name' => 'Test 45', 'age' => 450]),
            UsersModel::initStatic(['id' => 44, 'name' => 'Test 44', 'age' => 440]),
            UsersModel::initStatic(['id' => 43, 'name' => 'Test 43', 'age' => 430]),
            UsersModel::initStatic(['id' => 42, 'name' => 'Test 42', 'age' => 420]),
            UsersModel::initStatic(['id' => 41, 'name' => 'Test 41', 'age' => 410]),
        ];

        $this->assertEquals([
            'total' => 41,      // 45 - 5 + 1 
            'per_page' => 5,
            'current_page' => 1,
            'last_page' => 9,   // ceil( 41 / 5 )
            'from' => 1,
            'to' => 5,
            'data' => $expectedData,
        ], $actual);
    }

    private function paginationTest_thirdPage()
    {
        $_GET['page'] = 3;

        $actual = UsersModel::where('age', '>=', '50')
            ->where('age', '<=', '450')
            ->orderByDesc('id')
            ->paginate(5);

        $expectedData = [
            UsersModel::initStatic(['id' => 35, 'name' => 'Test 35', 'age' => 350]),
            UsersModel::initStatic(['id' => 34, 'name' => 'Test 34', 'age' => 340]),
            UsersModel::initStatic(['id' => 33, 'name' => 'Test 33', 'age' => 330]),
            UsersModel::initStatic(['id' => 32, 'name' => 'Test 32', 'age' => 320]),
            UsersModel::initStatic(['id' => 31, 'name' => 'Test 31', 'age' => 310]),
        ];

        $this->assertEquals([
            'total' => 41,      // 45 - 5 + 1 
            'per_page' => 5,
            'current_page' => 3,
            'last_page' => 9,   // ceil( 41 / 5 )
            'from' => 11,
            'to' => 15,
            'data' => $expectedData,
        ], $actual);
        
        unset($_GET['page']);
    }

}