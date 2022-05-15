<?php

require_once __DIR__ . '/bootstrap_for_examples.php';

use DfTools\SlimOrm\DB;
use DfTools\SlimOrm\Model;

/**
 * Example of the user model class.
 */
class UserModel extends Model
{

    protected $table = 'users';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

}


$userName = 'Random User - ' . uniqid();

$user = UserModel::where('name', $userName)->first();

if (! $user) {
    $userId = UserModel::insert([
        'name' => $userName,
        'age' => rand(20, 50)
    ]);

    $user = UserModel::find($userId);
}

?>
    <h1>User model object:</h1>
    <pre><?= htmlentities(print_r($user, true)) ?></pre>
    <hr>
<?php

$users = UserModel::where('id', '>', 0)
    ->orderByDesc('id')
    ->paginate(5);

?>
    <h1>Paginated result set (5 per page)</h1>
    <pre><?= htmlentities(print_r($users, true)) ?></pre>
    <hr>
<?php
