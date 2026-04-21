<?php

declare(strict_types=1);

class AllocationParameterService
{
    public function __construct(
        private readonly AllocationParameterRepository $parameters = new AllocationParameterRepository(),
        private readonly CourseRepository $courses = new CourseRepository(),
        private readonly StudentGroupRepository $groups = new StudentGroupRepository(),
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

        if ($this->parameters->findByCode((string) $payload['code']) !== null) {
            return ['success' => false, 'errors' => ['code' => ['Allocation parameter code already exists.']]];
        }

        $item = $this->parameters->create($payload);
        $this->auditLogs->record('create', 'allocation_parameter', $item->get('id'), null, $item->toArray(), $userId);

        return ['success' => true, 'allocation_parameter' => $item];
    }

    public function update(int|string $id, array $data, ?int $userId = null): array
    {
        $item = $this->parameters->find($id);
        if ($item === null) {
            return ['success' => false, 'errors' => ['allocation_parameter' => ['Allocation parameter not found.']]];
        }

        $payload = $this->normalize($data);
        $result = $this->validate($payload + $item->toArray());

        if (!$result['valid']) {
            return ['success' => false, 'errors' => $result['errors']];
        }

        if (isset($payload['code'])) {
            $existing = $this->parameters->findByCode((string) $payload['code']);
            if ($existing !== null && (int) $existing->get('id') !== (int) $item->get('id')) {
                return ['success' => false, 'errors' => ['code' => ['Allocation parameter code already exists.']]];
            }
        }

        $old = $item->toArray();
        $updated = $this->parameters->update($id, $payload);
        $this->auditLogs->record('update', 'allocation_parameter', $id, $old, $updated?->toArray(), $userId);

        return ['success' => true, 'allocation_parameter' => $updated];
    }

    private function normalize(array $data): array
    {
        $data = $this->validation->normalizeIntegers($data, ['course_id', 'student_group_id']);
        $data = $this->validation->normalizeBooleans($data, ['is_active']);

        if (!empty($data['course_code']) && empty($data['course_id'])) {
            $course = $this->courses->findByCode((string) $data['course_code']);
            if ($course !== null) {
                $data['course_id'] = (int) $course->get('id');
            }
        }

        if (!empty($data['student_group_code']) && empty($data['student_group_id'])) {
            $group = $this->groups->findByCode((string) $data['student_group_code']);
            if ($group !== null) {
                $data['student_group_id'] = (int) $group->get('id');
            }
        }

        if (isset($data['rule_data']) && !is_array($data['rule_data'])) {
            $decoded = json_decode((string) $data['rule_data'], true);
            $data['rule_data'] = is_array($decoded) ? $decoded : ['value' => $data['rule_data']];
        }

        if (isset($data['rule_data']) && is_array($data['rule_data'])) {
            $data['rule_data'] = json_encode($data['rule_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        unset($data['course_code'], $data['student_group_code']);

        return $data;
    }

    private function validate(array $data): array
    {
        return $this->validation->validate($data, [
            'course_id' => 'nullable|integer|min:1',
            'student_group_id' => 'nullable|integer|min:1',
            'code' => 'required|string|max:80',
            'name' => 'required|string|max:190',
            'rule_data' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
    }
}
