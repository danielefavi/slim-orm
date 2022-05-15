<?php

namespace DfTools\SlimOrm\Tests\Unit;

use DfTools\SlimOrm\QueryBuilder;
use DfTools\SlimOrm\Tests\Lib\AccessibilitySwapper;
use DfTools\SlimOrm\Tests\Lib\BaseTestClass;
use DfTools\SlimOrm\Tests\Lib\QueryBuilderCallableTrait;

class QueryBuilderTest extends BaseTestClass
{
    use QueryBuilderCallableTrait;
    
    /**
     * Return a new QueryBuilder instance.
     *
     * @return QueryBuilder
     */
    private function newQuery(): QueryBuilder
    {
        return new QueryBuilder($this->db());
    }

    /** @test */
    public function assert_expected_callable_methods_for_testing_are_up_to_date()
    {
        $this->assertEqualsCanonicalizing(
            $this->notExtCallableMethods,
            AccessibilitySwapper::getStaticPropertyValue(QueryBuilder::class, 'notExtCallableMethods')
        );

        $this->assertEqualsCanonicalizing(
            $this->callableMethodsFromModel,
            AccessibilitySwapper::getStaticPropertyValue(QueryBuilder::class, 'callableMethodsFromModel')
        );
    }

    /** @test */
    public function the_model_instance_should_not_be_able_to_invoke_the_functions_in_notExtCallableMethods()
    {
        $query = $this->newQuery();
        $aSwapper = new AccessibilitySwapper($query);

        foreach (get_class_methods($query) as $method) {
            $this->assertEquals(
                !in_array($method, $this->notExtCallableMethods),
                $aSwapper->invokeMethod('methodIsCallable', $method, true)
            );
        }
    }

    /** @test */
    public function the_not_model_instance_should_not_be_able_to_invoke_the_functions_in_notExtCallableMethods_and_callableMethodsFromModel()
    {
        $query = $this->newQuery();
        $aSwapper = new AccessibilitySwapper($query);

        foreach (get_class_methods($query) as $method) {
            $this->assertEquals(
                !in_array($method, array_merge($this->callableMethodsFromModel, $this->notExtCallableMethods)),
                $aSwapper->invokeMethod('methodIsCallable', $method)
            );
        }
    }

    /** @test */
    public function buildSelectQuery_should_return_the_text_of_the_query()
    {
        $query = $this->newQuery();

        $this->assertEquals(
            $this->buildSelectAndSanitize($query), 
            'SELECT *'
        );
    }

    /** @test */
    public function test_select_function()
    {
        $query = $this->newQuery()
            ->select('`field_1`, `field_2`');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query), 
            'SELECT `field_1`, `field_2`'
        );
    }

    /** @test */
    public function test_table_function()
    {
        $query = $this->newQuery()->table('my_db_table');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * FROM `my_db_table`"
        );

        $query = $this->newQuery()->table('`my_db_table`');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * FROM `my_db_table`"
        );
    }

    /** @test */
    public function test_join_function()
    {
        $query = $this->newQuery()
            ->join('my_db_table_2', '`my_db_table_1`.`col_1`', '=', '`my_db_table_2`.`col_2`');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * JOIN `my_db_table_2` ON `my_db_table_1`.`col_1` = `my_db_table_2`.`col_2`"
        );

        $query = $this->newQuery()
            ->join('my_db_table_2', '`my_db_table_1`.`col_1`', '=', '`my_db_table_2`.`col_2`')
            ->table('my_db_table_1');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * FROM `my_db_table_1` JOIN `my_db_table_2` ON `my_db_table_1`.`col_1` = `my_db_table_2`.`col_2`"
        );

        $query = $this->newQuery()
            ->join('my_db_table_2', '`my_db_table_1`.`col_1`', '=', '`my_db_table_2`.`col_2`', 'TEST_TYPE');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * TEST_TYPE JOIN `my_db_table_2` ON `my_db_table_1`.`col_1` = `my_db_table_2`.`col_2`"
        );
    }

    /** @test */
    public function test_leftJoin_function()
    {
        $query = $this->newQuery()
            ->leftJoin('my_db_table_2', '`my_db_table_1`.`col_1`', '=', '`my_db_table_2`.`col_2`');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * LEFT JOIN `my_db_table_2` ON `my_db_table_1`.`col_1` = `my_db_table_2`.`col_2`"
        );
    }

    /** @test */
    public function test_rightJoin_function()
    {
        $query = $this->newQuery()
            ->rightJoin('my_db_table_2', '`my_db_table_1`.`col_1`', '=', '`my_db_table_2`.`col_2`');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * RIGHT JOIN `my_db_table_2` ON `my_db_table_1`.`col_1` = `my_db_table_2`.`col_2`"
        );
    }

    /** @test */
    public function test_whereRaw_function()
    {
        $query = $this->newQuery()
            ->whereRaw("field = 'value'");

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * WHERE field = 'value'"
        );

        $query = $this->newQuery()
            ->whereRaw("field_1 = :field_1_val AND field_2 = :field_2_val", [ 
                'field_1_val' => 'some_value_1',
                'field_2_val' => 'some_value_2'
            ]);

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * WHERE field_1 = :field_1_val AND field_2 = :field_2_val"
        );

        $this->assertEqualsCanonicalizing([ 
            'field_1_val' => 'some_value_1',
            'field_2_val' => 'some_value_2'
        ], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function test_where_function()
    {
        $query = $this->newQuery()
            ->where('field_1', '>=', 12.34);

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * WHERE `field_1` >= :1_sql_data"
        );

        $this->assertEqualsCanonicalizing([ 
            '1_sql_data' => 12.34
        ], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function test_where_function_concatenation()
    {
        $query = $this->newQuery()
            ->where('field_1', '>=', 12.34)
            ->where('field_2', '=', 'test_2')
            ->where('field_3', '!=', 'test_3', 'AND')
            ->where('field_4', '!=', 'test_4', 'OR');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * WHERE `field_1` >= :1_sql_data AND `field_2` = :2_sql_data AND `field_3` != :3_sql_data OR `field_4` != :4_sql_data"
        );

        $this->assertEqualsCanonicalizing([ 
            '1_sql_data' => 12.34,
            '2_sql_data' => 'test_2',
            '3_sql_data' => 'test_3',
            '4_sql_data' => 'test_4',
        ], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function test_where_function_closure()
    {
        $query = $this->newQuery()
            ->where(function($subQuery) {
                $subQuery->where('field_1', '=', 'test_1')
                    ->where('field_2', '=', 'test_2', 'OR');
            })
            ->where(function($subQuery) {
                $subQuery->where('field_3', '=', 'test_3')
                    ->where('field_4', '=', 'test_4', 'OR');
            }, null, null, 'JUST_FOR_TESTING');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * WHERE ( `field_1` = :1_1_sql_data_sub_q OR `field_2` = :1_2_sql_data_sub_q ) JUST_FOR_TESTING " .
                           "( `field_3` = :3_1_sql_data_sub_q OR `field_4` = :3_2_sql_data_sub_q )"
        );

        $this->assertEqualsCanonicalizing([ 
            '1_1_sql_data_sub_q' => 'test_1',
            '1_2_sql_data_sub_q' => 'test_2',
            '3_1_sql_data_sub_q' => 'test_3',
            '3_2_sql_data_sub_q' => 'test_4',
        ], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function test_where_function__on_closure_the_extra_parameters_should_be_discarded()
    {
        $query = $this->newQuery()
            ->whereRaw('1=1')
            ->where(function($subQuery) {
                $subQuery->where('field_1', '=', 'test_1')
                    ->where('field_2', '=', 'test_2', 'OR');
            }, 'a', 'b', 'AND_OR_TEST');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * WHERE 1=1 AND_OR_TEST ( `field_1` = :1_1_sql_data_sub_q OR `field_2` = :1_2_sql_data_sub_q )"
        );

        $this->assertEqualsCanonicalizing([ 
            '1_1_sql_data_sub_q' => 'test_1',
            '1_2_sql_data_sub_q' => 'test_2',
        ], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function test_where__the_equal_operator_can_be_omitted()
    {
        $query = $this->newQuery()
            ->where('field_1', 'some_value');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * WHERE `field_1` = :1_sql_data"
        );

        $this->assertEqualsCanonicalizing([ 
            '1_sql_data' => 'some_value',
        ], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function test_orWhere_function()
    {
        $query = $this->newQuery()
            ->orWhere('a', '=', 'aa');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * WHERE `a` = :1_sql_data"
        );

        $this->assertEqualsCanonicalizing([ 
            '1_sql_data' => 'aa',
        ], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function test_orWhere_function_concatenation()
    {
        $query = $this->newQuery()
            ->orWhere('a', '=', 'aa')
            ->where('b', '=', 'bb')
            ->orWhere('c', '=', 'cc');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * WHERE `a` = :1_sql_data AND `b` = :2_sql_data OR `c` = :3_sql_data"
        );

        $this->assertEqualsCanonicalizing([ 
            '1_sql_data' => 'aa',
            '2_sql_data' => 'bb',
            '3_sql_data' => 'cc',
        ], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function test_orWhere_function_closure()
    {
        $query = $this->newQuery()
            ->whereRaw('1=1')
            ->orWhere(function($subQuery) {
                $subQuery->where('a', '=', 'aa')->orWhere('b', '!=', 'bb');
            }, 'should_be_discarded_1', 'should_be_discarded_2');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * WHERE 1=1 OR ( `a` = :1_1_sql_data_sub_q OR `b` != :1_2_sql_data_sub_q )"
        );

        $this->assertEqualsCanonicalizing([ 
            '1_1_sql_data_sub_q' => 'aa',
            '1_2_sql_data_sub_q' => 'bb',
        ], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function test_where_orWhere_closure_of_closure()
    {
        $query = $this->newQuery()
            ->where(function($subQuery) {
                $subQuery->where('a', '=', 'aa')
                    ->orWhere(function($ssQuery) {
                        $ssQuery->where('b', '<>', 'bb')->where('c', '=', 'cc');
                    })
                    ->where('d', '<>', 'dd');
            })
            ->orWhere(function($subQuery) {
                $subQuery->where('e', '=', 'ee')
                    ->orWhere(function($ssQuery) {
                        $ssQuery->where('f', '<>', 'ff')->where('g', '=', 'gg');
                    });
            });

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * WHERE " .
                "( `a` = :1_1_sql_data_sub_q OR ( `b` <> :1_2_1_sql_data_sub_q_sub_q AND `c` = :1_2_2_sql_data_sub_q_sub_q ) AND `d` <> :1_4_sql_data_sub_q ) OR " .
                "( `e` = :5_1_sql_data_sub_q OR ( `f` <> :5_2_1_sql_data_sub_q_sub_q AND `g` = :5_2_2_sql_data_sub_q_sub_q ) )"
        );

        $this->assertEqualsCanonicalizing([ 
            '1_1_sql_data_sub_q'            => 'aa',
            '1_2_1_sql_data_sub_q_sub_q'    => 'bb',
            '1_2_2_sql_data_sub_q_sub_q'    => 'cc',
            '1_4_sql_data_sub_q'            => 'dd',
            '5_1_sql_data_sub_q'            => 'ee',
            '5_2_1_sql_data_sub_q_sub_q'    => 'ff',
            '5_2_2_sql_data_sub_q_sub_q'    => 'gg',
        ], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function test_orWhere__the_equal_operator_can_be_omitted()
    {
        $query = $this->newQuery()
            ->where('field_1', 'some_value_1')
            ->orWhere('field_2', 'some_value_2');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * WHERE `field_1` = :1_sql_data OR `field_2` = :2_sql_data"
        );

        $this->assertEqualsCanonicalizing([ 
            '1_sql_data' => 'some_value_1',
            '2_sql_data' => 'some_value_2',
        ], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }
    
    /** @test */
    public function test_whereNull_function()
    {
        $query = $this->newQuery()
            ->whereNull('field_1');
        
        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * WHERE `field_1` IS NULL"
        );

        $query = $this->newQuery()
            ->whereNull('field_1')
            ->orWhereNull('field_2')
            ->orWhereNull('field_3')
            ->where(function($subQuery) {
                $subQuery->whereNull('field_4')
                    ->orWhereNull('field_5');
            });
        
        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * WHERE `field_1` IS NULL OR `field_2` IS NULL OR `field_3` IS NULL AND ( `field_4` IS NULL OR `field_5` IS NULL )"
        );

        $this->assertEqualsCanonicalizing([], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function test_limit_function()
    {
        $query = $this->newQuery()
            ->limit(321);
        
        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * LIMIT 321"
        );

        $query = $this->newQuery()
            ->table('my_table')
            ->where('some_field', '=', 'some_value')
            ->limit(444);
        
        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * FROM `my_table` WHERE `some_field` = :1_sql_data LIMIT 444"
        );

        $this->assertEqualsCanonicalizing([
            '1_sql_data' => 'some_value'
        ], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function test_offset_function()
    {
        $query = $this->newQuery()
            ->offset(999);
        
        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * OFFSET 999"
        );

        $query = $this->newQuery()
            ->table('my_table')
            ->where('some_field', '=', 'some_value')
            ->offset(888)
            ->limit(111);
        
        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * FROM `my_table` WHERE `some_field` = :1_sql_data LIMIT 111 OFFSET 888"
        );

        $this->assertEqualsCanonicalizing([
            '1_sql_data' => 'some_value'
        ], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function the_offset_should_be_always_after_limit()
    {
        $query = $this->newQuery()
            ->offset(999)
            ->limit(111);

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * LIMIT 111 OFFSET 999"
        );
        $this->assertEqualsCanonicalizing([], (new AccessibilitySwapper($query))->getPropertyValue('data'));

        $query = $this->newQuery()
            ->limit(222)
            ->offset(888);

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * LIMIT 222 OFFSET 888"
        );
        $this->assertEqualsCanonicalizing([], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function test_orderBy_function()
    {
        $query = $this->newQuery()
            ->orderBy('col_1', 'col_2', 'col_3');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * ORDER BY `col_1` , `col_2` , `col_3`"
        );

        $query = $this->newQuery()
            ->table('my_table')
            ->where('some_field', '=', 'some_value')
            ->orderBy('col_1', 'col_2');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * FROM `my_table` WHERE `some_field` = :1_sql_data ORDER BY `col_1` , `col_2`"
        );

        $this->assertEqualsCanonicalizing([
            '1_sql_data' => 'some_value'
        ], (new AccessibilitySwapper($query))->getPropertyValue('data'));

        $query = $this->newQuery()
            ->orderBy('col_1', 'desc', 'col_2', 'col_3', 'asc', 'col_4', 'desc', 'col_5', 'asc')
            ->orderBy('col_6')
            ->orderBy('col_7', 'DESC');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * ORDER BY `col_1` DESC, `col_2` , `col_3` ASC, `col_4` DESC, `col_5` ASC, `col_6` , `col_7` DESC"
        );
    }

    /** @test */
    public function test_orderByAsc_function()
    {
        $query = $this->newQuery()
            ->orderByAsc('col_1')
            ->orderByAsc('col_2');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * ORDER BY `col_1` ASC, `col_2` ASC"
        );
    }

    /** @test */
    public function test_orderByDesc_function()
    {
        $query = $this->newQuery()
            ->orderByDesc('col_1')
            ->orderByDesc('col_2');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * ORDER BY `col_1` DESC, `col_2` DESC"
        );
    }

    /** @test */
    public function test_orderBy_asc_and_desc_function()
    {
        $query = $this->newQuery()
            ->orderByDesc('col_1')
            ->orderByAsc('col_2');

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * ORDER BY `col_1` DESC, `col_2` ASC"
        );

        $this->assertEqualsCanonicalizing([], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function the_order_clause_should_be_always_before_limit_and_offset()
    {
        $query = $this->newQuery()
            ->orderBy('col_1', 'col_2')
            ->offset(999)
            ->limit(111);

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * ORDER BY `col_1` , `col_2` LIMIT 111 OFFSET 999"
        );
        $this->assertEqualsCanonicalizing([], (new AccessibilitySwapper($query))->getPropertyValue('data'));

        $query = $this->newQuery()
            ->offset(999)
            ->orderBy('col_1', 'col_2')
            ->limit(111);

        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * ORDER BY `col_1` , `col_2` LIMIT 111 OFFSET 999"
        );
        $this->assertEqualsCanonicalizing([], (new AccessibilitySwapper($query))->getPropertyValue('data'));

        $query = $this->newQuery()
            ->offset(999)
            ->limit(111)
            ->orderBy('col_1', 'col_2');
        
        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * ORDER BY `col_1` , `col_2` LIMIT 111 OFFSET 999"
        );
        $this->assertEqualsCanonicalizing([], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function test_groupBy_function()
    {
        $query = $this->newQuery()
            ->groupBy('col_1', 'col_2');
        
        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * GROUP BY `col_1`, `col_2`"
        );
        $this->assertEqualsCanonicalizing([], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function the_group_by_should_be_before_the_offset_limit_and_order_by()
    {
        $query = $this->newQuery()
            ->offset(999)
            ->limit(111)
            ->orderBy('col_1', 'col_2')
            ->groupBy('col_a', 'col_b');
    
        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * GROUP BY `col_a`, `col_b` ORDER BY `col_1` , `col_2` LIMIT 111 OFFSET 999"
        );
        $this->assertEqualsCanonicalizing([], (new AccessibilitySwapper($query))->getPropertyValue('data'));

        $query = $this->newQuery()
            ->offset(999)
            ->groupBy('col_a', 'col_b')
            ->limit(111)
            ->orderBy('col_1', 'col_2');
    
        $this->assertEquals(
            $this->buildSelectAndSanitize($query),
            "SELECT * GROUP BY `col_a`, `col_b` ORDER BY `col_1` , `col_2` LIMIT 111 OFFSET 999"
        );
        $this->assertEqualsCanonicalizing([], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /* ********************************************************************** */
    /* *******************          UPDATE TESTS          ******************* */
    /* ********************************************************************** */

    /** @test */
    public function base_query_update_test()
    {
        $query = $this->newQuery();
        
        $this->assertEquals(
            'UPDATE SET',
            $this->buildUpdateAndSanitize($query, [])
        );
        $this->assertEqualsCanonicalizing([], (new AccessibilitySwapper($query))->getPropertyValue('data'));

        $this->assertEquals(
            'UPDATE SET `f1` = :f1 , `f2` = :f2',
            $this->buildUpdateAndSanitize($query, [ 'f1' => 'v1', 'f2' => 'v2' ])
        );
        $this->assertEqualsCanonicalizing([], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function query_update_test()
    {
        $query = $this->newQuery()
            ->table('test_table')
            ->where('a', '>=', 'aaa')
            ->orWhere(function($subQuery) {
                $subQuery->where('b', '<=', 'bbb')->where('c', '!=', 'ccc');
            });
        
        $this->assertEquals(
            'UPDATE `test_table` SET WHERE `a` >= :1_sql_data OR ( `b` <= :2_1_sql_data_sub_q AND `c` != :2_2_sql_data_sub_q )',
            $this->buildUpdateAndSanitize($query, [])
        );

        $this->assertEqualsCanonicalizing([ 
            '1_sql_data'            => 'aaa',
            '2_1_sql_data_sub_q'    => 'bbb',
            '2_2_sql_data_sub_q'    => 'ccc',
        ], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function update_test()
    {
        $this->refreshDb();

        $this->db()->execStatement("INSERT INTO users (`name`, `age`) VALUES ('Mark', 20)");
        $this->db()->execStatement("INSERT INTO users (`name`, `age`) VALUES ('John', 30)");
        $this->db()->execStatement("INSERT INTO users (`name`, `age`) VALUES ('Mary', 40)");

        $this->assertEquals(0, $this->db()->table('users')->where('age', '<=', 10)->count());
        $this->assertEquals(0, $this->db()->table('users')->where('name', '=', 'Test')->count());

        $this->newQuery()
            ->table('users')
            ->where('age', '>=', 30)
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
    public function base_query_insert_test()
    {
        $query = $this->newQuery();
        
        $this->assertEquals(
            'INSERT INTO () VALUES ()',
            $this->buildInsertAndSanitize($query, [])
        );
        $this->assertEqualsCanonicalizing([], (new AccessibilitySwapper($query))->getPropertyValue('data'));

        $this->assertEquals(
            'INSERT INTO ( `f1` , `f2` ) VALUES ( :f1 , :f2 )',
            $this->buildInsertAndSanitize($query, [ 'f1' => 'v1', 'f2' => 'v2' ])
        );
        $this->assertEqualsCanonicalizing([], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function insert_test()
    {
        $this->refreshDb();

        $this->assertEquals(0, $this->db()->table('users')->count());

        $id = $this->newQuery()
            ->table('users')
            ->insert([
                'name' => 'Test 1',
                'age' => 99
            ]);
        
        $this->assertEquals(1, $id);
        $this->assertEquals(1, $this->db()->table('users')->count());

        $id = $this->newQuery()
            ->table('users')
            ->insert([
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
    public function base_query_delete_test()
    {
        $query = $this->newQuery();
        
        $this->assertEquals(
            'DELETE FROM',
            $this->buildDeleteAndSanitize($query, [])
        );
        $this->assertEqualsCanonicalizing([], (new AccessibilitySwapper($query))->getPropertyValue('data'));

        $query = $this->newQuery()
            ->table('my_table')
            ->where('a', '=', 'b');
        
        $this->assertEquals(
            'DELETE FROM `my_table` WHERE `a` = :1_sql_data',
            $this->buildDeleteAndSanitize($query, [])
        );
        $this->assertEqualsCanonicalizing([
            '1_sql_data' => 'b'
        ], (new AccessibilitySwapper($query))->getPropertyValue('data'));
    }

    /** @test */
    public function delete_test()
    {
        $this->refreshDb();

        $this->newQuery()->table('users')->insert([ 'name' => 'Test 1', 'age' => 10 ]);
        $this->newQuery()->table('users')->insert([ 'name' => 'Test 2', 'age' => 20 ]);
        $this->newQuery()->table('users')->insert([ 'name' => 'Test 3', 'age' => 30 ]);

        $this->assertEquals(3, $this->db()->table('users')->count());

        $this->newQuery()
            ->table('users')
            ->where('age', '>', 15)
            ->delete();
        
        $this->assertEquals(1, $this->db()->table('users')->count());
        $this->assertEquals(0, $this->db()->table('users')->where('age', '>=', 15)->count());

        $this->newQuery()->table('users')->insert([ 'name' => 'Test 4', 'age' => 40 ]);
        $this->assertEquals(2, $this->db()->table('users')->count());

        $this->newQuery()
            ->table('users')
            ->delete();

        $this->assertEquals(0, $this->db()->table('users')->count());
    }

    /* ********************************************************************** */
    /* **********************        GET TESTS        *********************** */
    /* ********************************************************************** */

    /** @test */
    public function get_test_with_empty_table()
    {
        $this->refreshDb();

        $actual = $this->newQuery()->table('users')->get();
        $expected = $this->db()->query("SELECT * FROM `users`");

        $this->assertEquals($expected, $actual);
    }

    /** @test */
    public function get_test_get_all_records()
    {
        $this->refreshDb();

        $expectedUsers = [
            (object)[ 'id' => 1, 'name' => 'Test 1', 'age' => 10 ],
            (object)[ 'id' => 2, 'name' => 'Test 2', 'age' => 20 ],
            (object)[ 'id' => 3, 'name' => 'Test 3', 'age' => 30 ]
        ];

        foreach ($expectedUsers as $user) {
            $this->newQuery()->table('users')->insert((array)$user);
        }

        $actual = $this->newQuery()->table('users')->get();
        $expectedQueryResult = $this->db()->query("SELECT * FROM `users`");

        $this->assertEqualsCanonicalizing($expectedUsers, $actual);
        $this->assertEqualsCanonicalizing($expectedQueryResult, $actual);
    }

    /** @test */
    public function get_test_where_query()
    {
        $this->refreshDb();
        
        $users = $this->createFakeUsers(5);

        $expectedUsers = [
            (object)[ 'id' => 2, 'name' => 'Test 2', 'age' => 20 ],
            (object)[ 'id' => 3, 'name' => 'Test 3', 'age' => 30 ]
        ];

        $actual = $this->newQuery()->table('users')->where('age', '>', 15)->where('age', '<', '35')->get();
        $expectedQueryResult = $this->db()->query("SELECT * FROM `users` WHERE `age` > 15 AND `age` < 35");

        $this->assertEqualsCanonicalizing($expectedUsers, $actual);
        $this->assertEqualsCanonicalizing($expectedQueryResult, $actual);
    }

    /** @test */
    public function get_test_exception()
    {
        $this->refreshDb();

        try {
            $this->newQuery()->get();
        } catch (\Throwable $th) {
            $this->assertInstanceOf(\PDOException::class, $th);
        }

        try {
            $this->newQuery()->table('field_that_does_not_exist')->get();
        } catch (\Throwable $th) {
            $this->assertInstanceOf(\PDOException::class, $th);
        }
    }

    /** @test */
    public function get_generic_test()
    {
        $this->refreshDb();
        
        $users = $this->createFakeUsers(5);

        $expected = [
            (object)[ 'id' => 4, 'name' => 'Test 4', 'age' => 40 ],
            (object)[ 'id' => 3, 'name' => 'Test 3', 'age' => 30 ]
        ];

        $actual = $this->newQuery()
            ->table('users')
            ->where('name', '=', 'Test 2')
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
    public function first_test_with_empty_table()
    {
        $this->refreshDb();

        $actual = $this->newQuery()->table('users')->first();

        $this->assertEquals(null, $actual);
    }

    /** @test */
    public function first_test_exception()
    {
        $this->refreshDb();

        try {
            $this->newQuery()->first();
        } catch (\Throwable $th) {
            $this->assertInstanceOf(\PDOException::class, $th);
        }

        try {
            $this->newQuery()->table('field_that_does_not_exist')->first();
        } catch (\Throwable $th) {
            $this->assertInstanceOf(\PDOException::class, $th);
        }
    }

    /** @test */
    public function first_generic_test()
    {
        $this->refreshDb();
        
        $users = $this->createFakeUsers(5);

        $expected = (object)[ 'id' => 4, 'name' => 'Test 4', 'age' => 40 ];

        $actual = $this->newQuery()
            ->table('users')
            ->where('name', '=', 'Test 2')
            ->orWhere('age', '=', 30)
            ->orWhere(function($subQuery) {
                $subQuery->where('name', '=', 'Test 4')->where('age', '=', 40);
            })
            ->orderBy('id', 'DESC')
            ->first();
        
        $this->assertEquals($expected, $actual);
    }

    /* ********************************************************************** */
    /* *******************        COUNT TESTS        ************************ */
    /* ********************************************************************** */

    /** @test */
    public function count_test_with_empty_table()
    {
        $this->refreshDb();

        $actual = $this->newQuery()->table('users')->count();

        $this->assertEquals(0, $actual);
    }

    /** @test */
    public function count_test_exception()
    {
        $this->refreshDb();

        try {
            $this->newQuery()->count();
        } catch (\Throwable $th) {
            $this->assertInstanceOf(\PDOException::class, $th);
        }

        try {
            $this->newQuery()->table('field_that_does_not_exist')->count();
        } catch (\Throwable $th) {
            $this->assertInstanceOf(\PDOException::class, $th);
        }
    }

    /** @test */
    public function count_generic_test()
    {
        $this->refreshDb();
        
        $users = $this->createFakeUsers(5);

        $actual = $this->newQuery()
            ->table('users')
            ->where('name', '=', 'Test 2')
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
    public function min_test_with_empty_table()
    {
        $this->refreshDb();

        $actual = $this->newQuery()->table('users')->min('id');

        $this->assertEquals(null, $actual);
    }

    /** @test */
    public function min_test_exception()
    {
        $this->refreshDb();

        try {
            $this->newQuery()->min('id');
        } catch (\Throwable $th) {
            $this->assertInstanceOf(\PDOException::class, $th);
        }

        try {
            $this->newQuery()->table('field_that_does_not_exist')->min('id');
        } catch (\Throwable $th) {
            $this->assertInstanceOf(\PDOException::class, $th);
        }
    }

    /** @test */
    public function min_generic_test()
    {
        $this->refreshDb();
        
        $users = $this->createFakeUsers(5);

        $actual = $this->newQuery()
            ->table('users')
            ->where('name', '=', 'Test 2')
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
    public function max_test_with_empty_table()
    {
        $this->refreshDb();

        $actual = $this->newQuery()->table('users')->max('id');

        $this->assertEquals(null, $actual);
    }

    /** @test */
    public function max_test_exception()
    {
        $this->refreshDb();

        try {
            $this->newQuery()->max('id');
        } catch (\Throwable $th) {
            $this->assertInstanceOf(\PDOException::class, $th);
        }

        try {
            $this->newQuery()->table('field_that_does_not_exist')->max('id');
        } catch (\Throwable $th) {
            $this->assertInstanceOf(\PDOException::class, $th);
        }
    }

    /** @test */
    public function max_generic_test()
    {
        $this->refreshDb();
        
        $users = $this->createFakeUsers(5);

        $actual = $this->newQuery()
            ->table('users')
            ->where('name', '=', 'Test 2')
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
    public function paginate_test_with_empty_table()
    {
        $this->refreshDb();

        $actual = $this->newQuery()->table('users')->paginate();

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
    public function paginate_test_exception()
    {
        $this->refreshDb();

        try {
            $this->newQuery()->paginate();
        } catch (\Throwable $th) {
            $this->assertInstanceOf(\PDOException::class, $th);
        }

        try {
            $this->newQuery()->table('field_that_does_not_exist')->paginate();
        } catch (\Throwable $th) {
            $this->assertInstanceOf(\PDOException::class, $th);
        }
    }

    /** @test */
    public function paginate_generic_test_cl()
    {
        $this->refreshDb();
        
        $users = $this->createFakeUsers(100);

        $this->paginationTest_firstPage();
        $this->paginationTest_thirdPage();
    }

    private function paginationTest_firstPage()
    {
        $actual = $this->newQuery()
            ->table('users')
            ->where('age', '>=', '50')
            ->where('age', '<=', '450')
            ->orderByDesc('id')
            ->paginate(5);
        
        $expectedData = [
            (object)['id' => 45, 'name' => 'Test 45', 'age' => 450],
            (object)['id' => 44, 'name' => 'Test 44', 'age' => 440],
            (object)['id' => 43, 'name' => 'Test 43', 'age' => 430],
            (object)['id' => 42, 'name' => 'Test 42', 'age' => 420],
            (object)['id' => 41, 'name' => 'Test 41', 'age' => 410],
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

        $actual = $this->newQuery()
            ->table('users')
            ->where('age', '>=', '50')
            ->where('age', '<=', '450')
            ->orderByDesc('id')
            ->paginate(5);
        
        $expectedData = [
            (object)['id' => 35, 'name' => 'Test 35', 'age' => 350],
            (object)['id' => 34, 'name' => 'Test 34', 'age' => 340],
            (object)['id' => 33, 'name' => 'Test 33', 'age' => 330],
            (object)['id' => 32, 'name' => 'Test 32', 'age' => 320],
            (object)['id' => 31, 'name' => 'Test 31', 'age' => 310],
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

    /* ********************************************************************** */
    /* ********************************************************************** */
    /* ********************************************************************** */

}
