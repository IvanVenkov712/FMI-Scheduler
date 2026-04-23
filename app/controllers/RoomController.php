<?php

declare(strict_types=1);

class RoomController extends CrudController
{
    public function __construct(
        array $request = [],
        private readonly RoomService $roomsService = new RoomService(),
        private readonly FloorService $floors = new FloorService(),
        private readonly ResourceService $resources = new ResourceService()
    ) {
        parent::__construct($request);
    }

    protected string $viewPath = 'rooms';
    protected string $entityName = 'room';
    protected array $fillable = [
        'floor_id', 'room_type_id', 'code', 'name', 'description', 'capacity',
        'covid_capacity', 'x', 'y', 'width', 'height', 'online_url', 'access_key', 'status',
    ];
    protected array $required = ['room_type_id', 'code', 'name'];
    protected array $integerFields = ['floor_id', 'room_type_id', 'capacity', 'covid_capacity', 'x', 'y', 'width', 'height'];
    protected array $listFilters = ['floor_id', 'room_type_id', 'status'];

    public function store(): mixed
    {
        $data = $this->payload();
        $result = $this->roomsService->create($this->normalize($data), [], $this->currentUserId());

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, $this->viewPath . '/create', $this->formData());
        }

        /** @var Room|null $room */
        $room = $result['room'] ?? null;

        return $this->storedResponse($room?->toArray() ?? []);
    }

    public function update(int|string $id): mixed
    {
        $data = $this->payload();
        $result = $this->roomsService->update($id, $this->normalize($data), null, $this->currentUserId());

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, $this->viewPath . '/edit', array_merge($this->formData(), [
                $this->entityName => array_merge($this->findOrFail($id), $data),
            ]));
        }

        /** @var Room|null $room */
        $room = $result['room'] ?? null;

        return $this->updatedResponse($room?->toArray() ?? $this->findOrFail($id));
    }

    public function delete(int|string $id): mixed
    {
        $deleted = $this->roomsService->delete($id, $this->currentUserId());

        if (!$deleted) {
            http_response_code(404);
            throw new RuntimeException('Room not found.');
        }

        return $this->deletedResponse();
    }

    public function availability(int|string $id): mixed
    {
        $from = (string) $this->input('from', date('Y-m-d 00:00:00'));
        $to = (string) $this->input('to', date('Y-m-d 23:59:59', strtotime('+7 days')));
        $overview = $this->roomsService->availabilityOverview((int) $id, $from, $to);

        return $this->respond('rooms/availability', [
            'room' => $this->findOrFail($id),
            'from' => $from,
            'to' => $to,
            'events' => array_map(static fn (Event $event): array => $event->toArray(), $overview['events']),
            'unavailability' => $overview['unavailability'],
        ]);
    }

    public function searchAvailable(): mixed
    {
        $capacity = (int) $this->input('capacity', 0);
        $startsAt = (string) $this->input('starts_at', '');
        $endsAt = (string) $this->input('ends_at', '');
        $deliveryMode = (string) $this->input('delivery_mode', 'physical');
        $resources = $this->resourceFilter();

        return $this->respond('rooms/index', [
            'rooms' => array_map(
                static fn (Room $room): array => $room->toArray(),
                $this->roomsService->availableBetween($capacity, $startsAt, $endsAt, $deliveryMode, $resources)
            ),
        ]);
    }

    protected function formData(): array
    {
        return [
            'floors' => array_map(
                static fn (Floor $floor): array => $floor->toArray(),
                $this->floors->listing()
            ),
            'room_types' => $this->roomsService->roomTypes(),
            'resources' => array_map(
                static fn (Resource $resource): array => $resource->toArray(),
                $this->resources->listing()
            ),
        ];
    }

    private function resourceFilter(): array
    {
        $resources = $this->input('resources', []);
        $quantities = $this->input('resource_quantities', []);

        if (is_string($resources)) {
            $resources = explode(',', $resources);
        }

        if (is_string($quantities)) {
            $quantities = explode(',', $quantities);
        }

        return ResourceRequirementHelper::normalize(
            is_array($resources) ? $resources + ['quantities' => (array) $quantities] : []
        );
    }

    protected function listRecords(array $filters = []): array
    {
        return $this->roomsService->listing($filters);
    }

    protected function findRecord(int|string $id): ?array
    {
        return $this->roomsService->find($id)?->toArray();
    }
}
