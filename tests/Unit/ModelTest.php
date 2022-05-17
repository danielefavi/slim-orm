<?php

namespace DfTools\SlimOrm\Tests\Unit;

use DfTools\SlimOrm\DB;
use DfTools\SlimOrm\SlimOrmException;
use DfTools\SlimOrm\QueryBuilder;
use DfTools\SlimOrm\Tests\Lib\AccessibilitySwapper;
use DfTools\SlimOrm\Tests\Lib\BaseTestClass;
use DfTools\SlimOrm\Tests\Lib\UsersModel;
use DfTools\SlimOrm\Tests\Lib\QueryBuilderCallableTrait;

/**
 * @todo: test functions of $this->callableMethodsFromModel one by one
 */
class ModelTest extends BaseTestClass
{
    use QueryBuilderCallableTrait;
    
    /** @test */
    public function some_methods_of_the_query_builder_are_not_callable_from_the_model()
    {
        $this->db(); // calling this method just to make sure the DB object is initialized

        $notCallableMEthods = array_merge($this->notExtCallableMethods);

        foreach ($notCallableMEthods as $method) {
            try {
                UsersModel::$method();
            } catch (\Throwable $th) {
                $this->assertInstanceOf(SlimOrmException::class, $th);
                $this->assertEquals(
                    'Call to undefined static method ' . UsersModel::class . '::' . $method . '()',
                    $th->getMessage()
                );
            }
        }

        $user = new UsersModel;

        foreach ($notCallableMEthods as $method) {
            try {
                $user->$method();
            } catch (\Throwable $th) {
                $this->assertInstanceOf(SlimOrmException::class, $th);
                $this->assertEquals(
                    'Call to undefined method ' . UsersModel::class . '::' . $method . '()',
                    $th->getMessage()
                );
            }
        }
    }

    /** @test */
    public function some_functions_of_query_builder_are_callable_from_the_model_object()
    {
        $this->db(); // calling this method just to make sure the DB object is initialized

        $user = new UsersModel;

        foreach (get_class_methods(QueryBuilder::class) as $method) {
            if (in_array($method, array_merge(['__construct'], $this->callableMethodsFromModel, $this->notExtCallableMethods))) continue;

            $params = $this->getMethodParams($method);

            $query = UsersModel::$method(...$params);
            $this->assertInstanceOf(QueryBuilder::class, $query);

            $query = $user->$method(...$params);
            $this->assertInstanceOf(QueryBuilder::class, $query);
        }
    }

    /** @test */
    public function the_getter_magic_method_should_get_the_data_from_the_data_attribute()
    {
        $expected = [
            'name' => 'John',
            'age' => 23,
            'random_attr' => 'value 1'
        ];

        $user = new UsersModel($expected);

        $swapper = new AccessibilitySwapper($user);

        $dataObj = $swapper->getPropertyValue('data');

        $this->assertEquals($expected, $dataObj);
        $this->assertEquals($expected['name'], $user->name);
        $this->assertEquals($expected['age'], $user->age);
        $this->assertEquals($expected['random_attr'], $user->random_attr);
    }

    /** @test */
    public function the_setter_magic_method_should_set_the_data_in_the_data_attribute()
    {
        $user = new UsersModel;
        
        $user->name = 'Mark';
        $user->age = 19;
        $user->phoneNumber = '12344566';

        $swapper = new AccessibilitySwapper($user);
        
        $this->assertEquals([
            'name' => 'Mark',
            'age' => 19,
            'phoneNumber' => '12344566'
        ], $swapper->getPropertyValue('data'));
    }

    /** @test */
    public function the_magic_method_isset_should_check_for_the_values_in_the_data_attribute()
    {
        $user = new UsersModel;
        
        $swapper = new AccessibilitySwapper($user);
        
        $data = $swapper->getPropertyValue('data');
        $this->assertFalse(isset($user->user));
        $this->assertFalse(isset($data['user']));

        $user->user = 'Mary';

        $data = $swapper->getPropertyValue('data');
        $this->assertTrue(isset($user->user));
        $this->assertTrue(isset($data['user']));
    }

    /** @test */
    public function the_unset_magic_method_should_unset_the_data_in_the_data_attribute()
    {
        $userInitialData = [
            'name' => 'Mark',
            'age' => 19,
            'phoneNumber' => '12344566'
        ];

        $user = new UsersModel($userInitialData);
        
        $swapper = new AccessibilitySwapper($user);

        $this->assertEquals($userInitialData, $swapper->getPropertyValue('data'));

        unset($user->age);

        $this->assertEquals([
            'name' => 'Mark',
            'phoneNumber' => '12344566'
        ], $swapper->getPropertyValue('data'));

        $this->assertNull($user->age);
    }

    /** @test */
    public function find_test()
    {
        $this->refreshDb();

        $users = $this->createFakeUsers(10);

        $user = UsersModel::find($users[5]->id);
        $this->assertEquals((array)$users[5], $user->toArray());

        $user2 = UsersModel::find($users[7]->name, 'name');
        $this->assertEquals((array)$users[7], $user2->toArray());
    }

    /** @test */
    public function create_test()
    {
        $this->refreshDb();
        
        $userData = (array)$this->generateFakeUsersData(1)[0];

        $res = $this->db()->query("SELECT count(*) `tot` FROM `users`");
        $this->assertEquals(0, $res[0]->tot);

        $user = UsersModel::create($userData);
        $this->assertEquals($userData, $user->toArray());
    }

    /** @test */
    public function the_initModelData_should_set_the_primary_key_value()
    {
        $user = new UsersModel([
            'id' => 987,
            'name' => 'John'
        ]);

        $swapper = new AccessibilitySwapper($user);
        
        $this->assertEquals('id', $swapper->getPropertyValue('primaryKey'));
        $this->assertNull($swapper->getPropertyValue('primaryKeyValue'));

        $swapper->invokeMethod('initModelData');

        $this->assertEquals(987, $swapper->getPropertyValue('primaryKeyValue'));


        $user2 = new UsersModel;

        $swapper2 = new AccessibilitySwapper($user2);
        $swapper->invokeMethod('initModelData', [
            'id' => 543,
            'name' => 'Susy'
        ]);

        $this->assertEquals(543, $swapper->getPropertyValue('primaryKeyValue'));
    }

    /** @test */
    public function the_initStatic_should_set_the_primary_key_value()
    {
        $user = UsersModel::initStatic([
            'id' => 235,
            'name' => 'John'
        ]);

        $swapper = new AccessibilitySwapper($user);
        $this->assertEquals(235, $swapper->getPropertyValue('primaryKeyValue'));
    }

    /** @test */
    public function toArray_test()
    {
        $userData = [
            'id' => 235,
            'name' => 'John'
        ];

        $user = UsersModel::initStatic($userData);

        $this->assertEquals($user->toArray(), $userData);
    }

    /** @test */
    public function save_test()
    {
        $this->refreshDb();

        $this->assertEquals(0, $this->db()->table('users')->count());

        $user = new UsersModel;
        $user->name = 'Mario';
        $user->age = 99;
        $user->save();

        $count = $this->db()->table('users')
            ->where('name', '=', 'Mario')
            ->where('age', '=', 99)
            ->count();

        $this->assertEquals(1, $count);

        $this->createFakeUsersNoIds(10);
        $this->assertEquals(11, $this->db()->table('users')->count());

        $user->name = 'Clint';
        $user->save();

        $count = $this->db()->table('users')
            ->where('name', '=', 'Mario')
            ->where('age', '=', 99)
            ->count();
        $this->assertEquals(0, $count);

        $count = $this->db()->table('users')
            ->where('name', '=', 'Clint')
            ->where('age', '=', 99)
            ->count();
        $this->assertEquals(1, $count);

        $this->assertEquals(11, $this->db()->table('users')->count());
    }


}
