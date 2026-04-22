<?php

declare(strict_types=1);

class RequestRepository
{
    public function __construct(
        private readonly ?Database $database = null
    ) {
    }

    public function find(int|string $id): ?ExamRequest
    {
        return (new GenericModelRepository($this->database))->find(ExamRequest::class, $id);
    }

    public function all(array $filters = []): array
    {
        $sql = 'SELECT * FROM exam_requests WHERE 1=1';
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= ' AND status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['requested_by'])) {
            $sql .= ' AND requested_by = :requested_by';
            $params['requested_by'] = (int) $filters['requested_by'];
        }

        if (!empty($filters['preferred_delivery_mode'])) {
            $sql .= ' AND preferred_delivery_mode = :preferred_delivery_mode';
            $params['preferred_delivery_mode'] = $filters['preferred_delivery_mode'];
        }

        $sql .= ' ORDER BY created_at DESC, id DESC';

        $rows = $this->db()->fetchAll($sql, $params);

        return array_map(static fn (array $row): ExamRequest => new ExamRequest($row), $rows);
    }

    public function create(array $data): ExamRequest
    {
        if (isset($data['required_resources']) && is_array($data['required_resources'])) {
            $data['required_resources'] = json_encode($data['required_resources'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $request = new ExamRequest($data);
        $request->save();

        return $request;
    }

    public function update(int|string $id, array $data): ?ExamRequest
    {
        $request = $this->find($id);

        if ($request === null) {
            return null;
        }

        if (isset($data['required_resources']) && is_array($data['required_resources'])) {
            $data['required_resources'] = json_encode($data['required_resources'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $request->fill($data);
        $request->save();

        return $request;
    }

    public function review(int|string $id, string $status, ?int $reviewedBy = null, ?string $notes = null): ?ExamRequest
    {
        $request = $this->find($id);

        if ($request === null) {
            return null;
        }

        $payload = [
            'status' => $status,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ];

        if ($notes !== null) {
            $payload['notes'] = $notes;
        }

        return $this->update($id, $payload);
    }

    public function pending(int $limit = 50): array
    {
        $rows = $this->db()->fetchAll(
            'SELECT * FROM exam_requests WHERE status = "pending" ORDER BY created_at DESC, id DESC LIMIT ' . max(1, $limit)
        );

        return array_map(static fn (array $row): ExamRequest => new ExamRequest($row), $rows);
    }

    public function forUser(int $userId): array
    {
        $rows = $this->db()->fetchAll(
            'SELECT * FROM exam_requests WHERE requested_by = :requested_by ORDER BY created_at DESC, id DESC',
            ['requested_by' => $userId]
        );

        return array_map(static fn (array $row): ExamRequest => new ExamRequest($row), $rows);
    }

    public function delete(int|string $id): bool
    {
        $request = $this->find($id);

        return $request?->delete() ?? false;
    }

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }
}
