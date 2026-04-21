<?php

declare(strict_types=1);

class AllocationParameter extends Model
{
    protected string $table = 'allocation_parameters';
    protected array $fillable = [
        'course_id',
        'student_group_id',
        'code',
        'name',
        'rule_data',
        'description',
        'is_active',
    ];

    public function ruleData(): array
    {
        $value = $this->get('rule_data');

        if (!is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
