Slim ORM - A lightweight ORM
============================

Slim ORM is a lightweight PHP ORM with an API interface similar to the Laravel's ORM (Eloquent).

Please note this ORM has very basic query functionalities; for example it does not support relationships (like `belongsToMany` or `belogsTo`).  
Please check some examples below.

## Installation

## Installation via composer

```sh
composer require danielefavi/slim-orm
```

## Setup

Setting up the DB object with **MySQL**:

```php
   $config = [
       'connection' => 'mysql',
       'name' => 'database-name',
       'username' => 'root',
       'password' => 'your_password',
       'options' => [
           \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
       ],
   ];

   $pdo = new \PDO(
       'mysql:host=' . $config['connection'] . ';dbname=' . $config['name'],
       $config['username'],
       $config['password'],
       $config['options']
   );

   $db = DB::init($pdo);
```

Setting up the DB object with **SQLite**:

```php
   $pdo = new \PDO('sqlite:' . __DIR__ . '/sqlite.db');
   
   $db = DB::init($pdo);
```

## Usage

### Query Examples

#### Simple query example

```php
$res = DB::table('users')
   ->where('name', 'Piripoppi')
   ->orWhere('age', '>=', 30)
   ->get();
```

#### Pagination

Return the paginated result (10 items per page).

```php
$res = DB::table('users')
   ->orWhere('age', '>=', 30)
   ->paginate(10);
```

#### Full Query example

```php
$res = DB::table('users')
   ->join('comments', '`users`.`id`', '=', '`comments`.`user_id`');
   ->where('id', '>', 30)
   ->where('id', '<=', 44)
   ->where(function($query) {
      $query->where('age', '>=', 10)
         ->orWhere('age', '<=', 20);
   })
   ->where(function($query) {
      $query->whereNull('age')
         ->orWhere('age', 20000);
   })
   ->orderByAsc('id')
   ->orderByDesc('file')
   ->orderBy('file', 'asc', 'field1', 'field2', 'desc', 'field3')
   ->limit(5)
   ->offset(3)
   ->groupBy('file', 'id')
   ->get();
```

## Model

### Defining a model

The model class must:
- Extend the class `DfTools\SlimOrm\Model`.
- Specify the database table the model represents, in the protected attribute `$table`.
- Specify the primary key of the the database table of the model.

```php
use DfTools\SlimOrm\Model;

class UserModel extends Model
{
    protected $table = 'users';

    protected $primaryKey = 'id';
}
```

## Extra

### Unittest

```php
./vendor/bin/phpunit
```

### For more examples please check the `examples` folder.