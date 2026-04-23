<?php

declare(strict_types=1);

class EventController extends CrudController
{
    public function __construct(
        array $request = [],
        private readonly EventService $events = new EventService(),
        private readonly RoomService $rooms = new RoomService()
    ) {
        parent::__construct($request);
    }

    protected string $viewPath = 'events';
    protected string $entityName = 'event';
    protected array $fillable = [
        'created_by', 'room_id', 'title', 'type', 'description', 'course_name',
        'group_name', 'students_count', 'starts_at', 'ends_at', 'delivery_mode', 'status',
    ];
    protected array $required = ['title', 'starts_at', 'ends_at'];
    protected array $integerFields = ['created_by', 'room_id', 'students_count'];
    protected array $listFilters = ['type', 'status', 'room_id', 'delivery_mode'];

    public function store(): mixed
    {
        $data = $this->payload();
        $data['created_by'] ??= $this->currentUserId();
        $result = $this->events->create($this->normalize($data), $this->extractOnlineSession($data), $this->currentUserId());

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, $this->viewPath . '/create', $this->formData());
        }

        /** @var Event|null $event */
        $event = $result['event'] ?? null;

        if (!$event instanceof Event) {
            return $this->storedResponse([]);
        }

        if ($this->wantsJson()) {
            return $this->eventResponse($event, 201, 'Created successfully.');
        }

        return $this->storedResponse($event->toArray());
    }

    public function update(int|string $id): mixed
    {
        $data = $this->payload();
        $result = $this->events->update($id, $this->normalize($data), $this->extractOnlineSession($data), $this->currentUserId());

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, 'events/edit', array_merge($this->formData(), [
                'event' => array_merge($this->findOrFail($id), $data),
            ]));
        }

        /** @var Event|null $event */
        $event = $result['event'] ?? null;

        if (!$event instanceof Event) {
            return $this->updatedResponse($this->findOrFail($id));
        }

        if ($this->wantsJson()) {
            return $this->eventResponse($event, 200, 'Updated successfully.');
        }

        return $this->updatedResponse($event->toArray());
    }

    public function details(int|string $id): mixed
    {
        $event = $this->events->find($id);
        if ($event === null) {
            http_response_code(404);
            throw new RuntimeException('Event not found.');
        }

        return $this->eventResponse($event);
    }

    public function delete(int|string $id): mixed
    {
        $deleted = $this->events->delete($id, $this->currentUserId());

        if (!$deleted) {
            http_response_code(404);
            throw new RuntimeException('Event not found.');
        }

        return $this->deletedResponse();
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
            'rooms' => array_map(
                static fn (Room $room): array => $room->toArray(),
                $this->rooms->listing()
            ),
        ];
    }

    private function extractOnlineSession(array $data): ?array
    {
        $session = $data['online_session'] ?? null;

        if (is_string($session) && $session !== '') {
            $decoded = json_decode($session, true);
            $session = is_array($decoded) ? $decoded : null;
        }

        if (!is_array($session)) {
            $session = [];
        }

        $mappedFields = [
            'platform' => 'online_platform',
            'url' => 'online_url',
            'access_key' => 'online_access_key',
            'access_password' => 'online_access_password',
            'host_user_id' => 'online_host_user_id',
            'moderator_name' => 'online_moderator_name',
            'access_instructions' => 'online_access_instructions',
            'starts_at' => 'online_starts_at',
            'ends_at' => 'online_ends_at',
        ];

        foreach ($mappedFields as $target => $source) {
            if (array_key_exists($source, $data) && $data[$source] !== '') {
                $session[$target] = $data[$source];
            }
        }

        return $session === [] ? null : $session;
    }

    private function eventResponse(Event $event, int $statusCode = 200, string $message = 'OK'): mixed
    {
        $payload = [
            'event' => $event->toArray(),
            'online_session' => $event->onlineSession()?->toArray(),
        ];

        if ($this->wantsJson()) {
            http_response_code($statusCode);
            return $payload + ['message' => $message];
        }

        return $this->render($this->viewPath . '/details', $payload);
    }

    private function roomHasConflict(array $data): bool
    {
        return $this->events->hasRoomConflict(
            (int) ($data['room_id'] ?? 0),
            (string) ($data['starts_at'] ?? ''),
            (string) ($data['ends_at'] ?? ''),
            isset($data['id']) ? (int) $data['id'] : null
        );
    }

    protected function listRecords(array $filters = []): array
    {
        return $this->events->all($filters);
    }

    protected function findRecord(int|string $id): ?array
    {
        return $this->events->find($id)?->toArray();
    }
}
