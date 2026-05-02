<?php
namespace App\models;

use App\core\Model;

class ExamAssignment extends Model
{
    public $id = 0;
    public $exam_request_id = 0;
    public $room_id = 0;
    public $exam_date = '';
    public $start_time = '';
    public $end_time = '';
    public $buffer_before_minutes = 0;
    public $buffer_after_minutes = 0;
    public $allocation_status = 'draft';
    public $notes = '';
}
