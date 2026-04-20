<?php

declare(strict_types=1);

class EventController extends CrudController
{
    protected string $table = 'events';
    protected string $viewPath = 'events';
    protected string $entityName = 'event';
    protected array $fillable = [
        'created_by', 'room_id', 'title', 'type', 'description', 'course_name',
        'group_name', 'students_count', 'starts_at', 'ends_at', 'delivery_mode', 'status',
    ];
    protected array $required = ['title', 'starts_at', 'ends_at'];
    protected array $integerFields = ['created_by', 'room_id', 'students_count'];
    protected array $listFilters = ['type', 'status', 'room_id', 'delivery_mode'];
    protected string $defaultOrder = 'starts_at DESC';

    public function store(): mixed
    {
        $_POST['created_by'] ??= $this->currentUserId();
        return parent::store();
    }

    public function update(int|string $id): mixed
    {
        $data = $this->payload();
        $data['id'] = $id;
        $errors = $this->validate($data, true);

        if ($errors !== []) {
            return $this->validationResponse($errors, $data, 'events/edit', [
                'event' => array_merge($this->findOrFail($id), $data),
            ]);
        }

        unset($data['id']);
        $this->updateRow($id, $this->normalize($data));

        return $this->updatedResponse($this->findOrFail($id));
    }

    protected function domainErrors(array $data): array
    {
        $errors = [];

        if (!empty($data['starts_at']) && !empty($data['ends_at']) && strtotime((string) $data['ends_at']) <= strtotime((string) $data['starts_at'])) {
            $errors['ends_at'] = 'End time must be after start time.';
        }

        if (!empty($data['room_id']) && !empty($data['starts_at']) && !empty($data['ends_at']) && $this->roomHasConflict($data)) {
            $errors['room_id'] = 'The selected room is not available in this time interval.';
        }

        return $errors;
    }

    protected function formData(): array
    {
        return [
            'rooms' => $this->database()->fetchAll('SELECT id, code, name, capacity, status FROM rooms ORDER BY code ASC'),
        ];
    }

    private function roomHasConflict(array $data): bool
    {
        $params = [
            'room_id' => $data['room_id'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'event_id' => $data['id'] ?? 0,
        ];

        $conflict = $this->database()->fetch(
            'SELECT id FROM events
             WHERE room_id = :room_id
               AND id <> :event_id
               AND starts_at < :ends_at
               AND ends_at > :starts_at
               AND status <> "cancelled"
             LIMIT 1',
            $params
        );

        return $conflict !== null;
    }
}
