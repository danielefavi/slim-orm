<?php

require_once __DIR__ . '/bootstrap_for_examples.php';

use DfTools\SlimOrm\DB;

// User sample data
$users = [
    ['name' => 'Susy ', 'age' => 20],
    ['name' => 'Mary ', 'age' => 25],
    ['name' => 'Mark ', 'age' => 30],
    ['name' => 'John ', 'age' => 35],
];

foreach ($users as $user) {
    // checking if the user is in the database using COUNT
    $count = DB::table('users')
        ->where('name', $user['name'])
        ->count();

    // if the previous count returns 0 then it creates the user
    if (! $count) {
        DB::table('users')->insert([
            'name' => $user['name'], 
            'age' => $user['age']
        ]);
    }
}

// example of query.
$res = DB::table('users')
        ->where(function($query) {
            $query->where('age', '>=', 25)
                ->where('age', '<', 35);
        })
        ->orWhere('name', 'Susy')
        ->orderByDesc('id', 'name')
        ->get();

echo '<pre>';
var_dump($res);
die();