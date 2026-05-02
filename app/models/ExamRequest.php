<?php
namespace App\models;

use App\core\Model;

class ExamRequest extends Model
{
    public $id = 0;
    public $subject_name = '';
    public $teacher_name = '';
    public $student_count = 0;
    public $date = '';
    public $start_time = '';
    public $end_time = '';
    public $delivery_mode = 'physical';
    public $online_platform = '';
    public $online_access_key = '';
    public $status = 'pending';
}
