<?php
namespace App\models;

use App\Core\Model;

class User extends Model
{
    public $id = 0;
    public $username = "";
    public $first_name = '';
    public $last_name = '';
    public $email = '';
    public $password_hash = '';
    public $role = 'student';
    public $isActive = true;
}
