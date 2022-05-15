<?php

namespace DfTools\SlimOrm\Tests\Lib;

use DfTools\SlimOrm\Model;

class UsersModel extends Model
{

    /**
     * Store the name of the database table that the model represents.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

}