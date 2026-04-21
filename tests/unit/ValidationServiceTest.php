<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ValidationServiceTest extends TestCase
{
    private ValidationService $service;

    public function setUp(): void
    {
        $this->service = new ValidationService();
    }

    public function testValidateDateRangeRejectsInvalidOrder(): void
    {
        $errors = $this->service->validateDateRange(
            '2026-05-10 12:00:00',
            '2026-05-10 11:00:00',
            'preferred_starts_at',
            'preferred_ends_at'
        );

        $this->assertArrayHasKey('preferred_ends_at', $errors);
        $this->assertSame('The end date must be after the start date.', $errors['preferred_ends_at'][0]);
    }

    public function testNormalizeHelpersPrepareBackendPayloads(): void
    {
        $data = [
            'students_count' => '42',
            'preferred_room_id' => '7',
            'is_online' => '1',
            'required_resources' => ['camera', 'projector'],
        ];

        $data = $this->service->normalizeIntegers($data, ['students_count', 'preferred_room_id']);
        $data = $this->service->normalizeBooleans($data, ['is_online']);
        $data = $this->service->normalizeJson($data, ['required_resources']);

        $this->assertSame(42, $data['students_count']);
        $this->assertSame(7, $data['preferred_room_id']);
        $this->assertSame(1, $data['is_online']);
        $this->assertSame('["camera","projector"]', $data['required_resources']);
    }
}
