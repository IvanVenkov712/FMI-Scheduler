<?php

declare(strict_types=1);

class Course extends Model
{
    protected string $table = 'courses';
    protected array $fillable = [
        'code',
        'name',
        'description',
    ];
}
