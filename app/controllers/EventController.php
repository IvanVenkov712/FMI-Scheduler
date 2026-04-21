<?php

declare(strict_types=1);

class EventController extends CrudController
{
    public function __construct(
        array $request = [],
        private readonly EventService $events = new EventService()
    ) {
        parent::__construct($request);
    }

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
        $data = $this->payload();
        $data['created_by'] ??= $this->currentUserId();
        $result = $this->events->create($this->normalize($data), null, $this->currentUserId());

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, $this->viewPath . '/create', $this->formData());
        }

        /** @var Event|null $event */
        $event = $result['event'] ?? null;

        return $this->storedResponse($event?->toArray() ?? []);
    }

    public function update(int|string $id): mixed
    {
        $data = $this->payload();
        $result = $this->events->update($id, $this->normalize($data), null, $this->currentUserId());

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, 'events/edit', array_merge($this->formData(), [
                'event' => array_merge($this->findOrFail($id), $data),
            ]));
        }

        /** @var Event|null $event */
        $event = $result['event'] ?? null;

        return $this->updatedResponse($event?->toArray() ?? $this->findOrFail($id));
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
