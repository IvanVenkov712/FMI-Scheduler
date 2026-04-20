<?php

declare(strict_types=1);

class RequestController extends CrudController
{
    protected string $table = 'exam_requests';
    protected string $viewPath = 'requests';
    protected string $entityName = 'request';
    protected array $fillable = [
        'requested_by', 'event_id', 'course_name', 'students_count', 'preferred_room_id',
        'preferred_delivery_mode', 'preferred_starts_at', 'preferred_ends_at',
        'required_resources', 'notes', 'status', 'reviewed_by', 'reviewed_at',
    ];
    protected array $required = ['course_name', 'students_count'];
    protected array $integerFields = ['requested_by', 'event_id', 'students_count', 'preferred_room_id', 'reviewed_by'];
    protected array $jsonFields = ['required_resources'];
    protected array $listFilters = ['status', 'requested_by', 'preferred_delivery_mode'];
    protected string $defaultOrder = 'created_at DESC';

    public function store(): mixed
    {
        $_POST['requested_by'] ??= $this->currentUserId();
        return parent::store();
    }

    public function review(int|string $id): mixed
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->respond('requests/review', ['request' => $this->findOrFail($id)]);
        }

        $data = $this->payload();
        $status = (string) ($data['status'] ?? '');

        if (!in_array($status, ['approved', 'rejected', 'scheduled', 'cancelled'], true)) {
            return $this->validationResponse(['status' => 'Invalid review status.'], $data, 'requests/review', [
                'request' => $this->findOrFail($id),
            ]);
        }

        $this->updateRow($id, [
            'status' => $status,
            'reviewed_by' => $this->currentUserId(),
            'reviewed_at' => date('Y-m-d H:i:s'),
            'notes' => $data['notes'] ?? $this->findOrFail($id)['notes'],
        ]);

        return $this->updatedResponse($this->findOrFail($id));
    }

    protected function formData(): array
    {
        return [
            'rooms' => $this->database()->fetchAll('SELECT id, code, name, capacity FROM rooms ORDER BY code ASC'),
            'resources' => $this->database()->fetchAll('SELECT * FROM resources ORDER BY name ASC'),
        ];
    }
}
