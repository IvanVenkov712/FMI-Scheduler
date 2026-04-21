<?php

declare(strict_types=1);

class CourseService
{
    public function __construct(
        private readonly CourseRepository $courses = new CourseRepository(),
        private readonly AuditLogRepository $auditLogs = new AuditLogRepository(),
        private readonly ValidationService $validation = new ValidationService()
    ) {
    }

    public function create(array $data, ?int $userId = null): array
    {
        $result = $this->validation->validate($data, [
            'code' => 'required|string|max:80',
            'name' => 'required|string|max:190',
            'description' => 'nullable|string',
        ]);

        if (!$result['valid']) {
            return ['success' => false, 'errors' => $result['errors']];
        }

        if ($this->courses->findByCode((string) $data['code']) !== null) {
            return ['success' => false, 'errors' => ['code' => ['Course code already exists.']]];
        }

        $course = $this->courses->create($data);
        $this->auditLogs->record('create', 'course', $course->get('id'), null, $course->toArray(), $userId);

        return ['success' => true, 'course' => $course];
    }

    public function update(int|string $id, array $data, ?int $userId = null): array
    {
        $course = $this->courses->find($id);
        if ($course === null) {
            return ['success' => false, 'errors' => ['course' => ['Course not found.']]];
        }

        $result = $this->validation->validate($data + $course->toArray(), [
            'code' => 'required|string|max:80',
            'name' => 'required|string|max:190',
            'description' => 'nullable|string',
        ]);

        if (!$result['valid']) {
            return ['success' => false, 'errors' => $result['errors']];
        }

        if (isset($data['code'])) {
            $existing = $this->courses->findByCode((string) $data['code']);
            if ($existing !== null && (int) $existing->get('id') !== (int) $course->get('id')) {
                return ['success' => false, 'errors' => ['code' => ['Course code already exists.']]];
            }
        }

        $old = $course->toArray();
        $updated = $this->courses->update($id, $data);
        $this->auditLogs->record('update', 'course', $id, $old, $updated?->toArray(), $userId);

        return ['success' => true, 'course' => $updated];
    }
}
