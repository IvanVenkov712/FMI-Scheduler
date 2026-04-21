<?php

declare(strict_types=1);

class StudentGroup extends Model
{
    protected string $table = 'student_groups';
    protected array $fillable = [
        'course_id',
        'code',
        'name',
        'year_of_study',
        'description',
    ];
}
