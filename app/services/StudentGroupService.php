<?php

declare(strict_types=1);

class StudentGroupService
{
    public function __construct(
        private readonly StudentGroupRepository $groups = new StudentGroupRepository(),
        private readonly CourseRepository $courses = new CourseRepository(),
        private readonly AuditLogRepository $auditLogs = new AuditLogRepository(),
        private readonly ValidationService $validation = new ValidationService()
    ) {
    }

    public function create(array $data, ?int $userId = null): array
    {
        $payload = $this->normalize($data);
        $result = $this->validate($payload);

        if (!$result['valid']) {
            return ['success' => false, 'errors' => $result['errors']];
        }

        if ($this->groups->findByCode((string) $payload['code']) !== null) {
            return ['success' => false, 'errors' => ['code' => ['Student group code already exists.']]];
        }

        $group = $this->groups->create($payload);
        $this->auditLogs->record('create', 'student_group', $group->get('id'), null, $group->toArray(), $userId);

        return ['success' => true, 'student_group' => $group];
    }

    public function update(int|string $id, array $data, ?int $userId = null): array
    {
        $group = $this->groups->find($id);
        if ($group === null) {
            return ['success' => false, 'errors' => ['student_group' => ['Student group not found.']]];
        }

        $payload = $this->normalize($data);
        $result = $this->validate($payload + $group->toArray());

        if (!$result['valid']) {
            return ['success' => false, 'errors' => $result['errors']];
        }

        if (isset($payload['code'])) {
            $existing = $this->groups->findByCode((string) $payload['code']);
            if ($existing !== null && (int) $existing->get('id') !== (int) $group->get('id')) {
                return ['success' => false, 'errors' => ['code' => ['Student group code already exists.']]];
            }
        }

        $old = $group->toArray();
        $updated = $this->groups->update($id, $payload);
        $this->auditLogs->record('update', 'student_group', $id, $old, $updated?->toArray(), $userId);

        return ['success' => true, 'student_group' => $updated];
    }

    private function normalize(array $data): array
    {
        $data = $this->validation->normalizeIntegers($data, ['course_id', 'year_of_study']);

        if (!empty($data['course_code']) && empty($data['course_id'])) {
            $course = $this->courses->findByCode((string) $data['course_code']);
            if ($course !== null) {
                $data['course_id'] = (int) $course->get('id');
            }
        }

        unset($data['course_code']);

        return $data;
    }

    private function validate(array $data): array
    {
        return $this->validation->validate($data, [
            'course_id' => 'nullable|integer|min:1',
            'code' => 'required|string|max:80',
            'name' => 'required|string|max:190',
            'year_of_study' => 'nullable|integer|min:1|max:8',
            'description' => 'nullable|string',
        ]);
    }
}
