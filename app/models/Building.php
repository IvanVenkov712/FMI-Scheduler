<?php
namespace App\models;

use App\core\Model;

class Building extends Model
{
    public $id = 0;
    public $name = '';
    public $code = '';
    public $address = '';
    public $campus_area = '';
    public $vertical_view = 'north';
    public $floor_count = 0;
    public $is_active = true;
}
