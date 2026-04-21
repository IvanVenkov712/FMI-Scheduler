<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testNullableFieldSkipsRemainingRulesWhenEmpty(): void
    {
        $validator = Validator::make(
            ['notes' => ''],
            ['notes' => 'nullable|string|min:10']
        );

        $this->assertTrue($validator->passes());
        $this->assertSame([], $validator->errors());
    }

    public function testValidatorCollectsWorkflowRelevantErrors(): void
    {
        $validator = Validator::make(
            [
                'students_count' => 0,
                'delivery_mode' => 'remote',
                'confirmation' => 'abc',
                'password' => 'xyz',
            ],
            [
                'students_count' => 'required|integer|min:1',
                'delivery_mode' => 'required|in:physical,online,hybrid',
                'confirmation' => 'same:password',
            ]
        );

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();

        $this->assertArrayHasKey('students_count', $errors);
        $this->assertArrayHasKey('delivery_mode', $errors);
        $this->assertArrayHasKey('confirmation', $errors);
        $this->assertSame('The students count field must be at least 1.', $errors['students_count'][0]);
        $this->assertSame('The delivery mode field contains an invalid value.', $errors['delivery_mode'][0]);
        $this->assertSame('The confirmation field must match password.', $errors['confirmation'][0]);
    }
}
