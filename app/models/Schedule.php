<?php
namespace App\Models;
use App\Core\Model;

class Schedule extends Model 
{
    public $id = 0;
    public $name = '';
    public $description = '';
    public $start_date = '';
    public $end_date = '';
}