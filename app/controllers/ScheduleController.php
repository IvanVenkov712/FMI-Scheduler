<?php

declare(strict_types=1);

class ScheduleController extends CrudController
{
    public function __construct(
        array $request = [],
        private readonly ScheduleService $schedulesService = new ScheduleService()
    ) {
        parent::__construct($request);
    }

    protected string $table = 'schedules';
    protected string $viewPath = 'schedules';
    protected string $entityName = 'schedule';
    protected array $fillable = ['name', 'type', 'academic_year', 'semester', 'starts_on', 'ends_on', 'status', 'created_by'];
    protected array $required = ['name', 'starts_on', 'ends_on'];
    protected array $integerFields = ['created_by'];
    protected array $listFilters = ['type', 'status', 'semester'];
    protected string $defaultOrder = 'starts_on DESC';

    public function store(): mixed
    {
        $data = $this->payload();
        $data['created_by'] ??= $this->currentUserId();
        $result = $this->schedulesService->create($this->normalize($data), $this->currentUserId());

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, $this->viewPath . '/create');
        }

        /** @var Schedule|null $schedule */
        $schedule = $result['schedule'] ?? null;

        return $this->storedResponse($schedule?->toArray() ?? []);
    }

    public function update(int|string $id): mixed
    {
        $data = $this->payload();
        $result = $this->schedulesService->update($id, $this->normalize($data), $this->currentUserId());

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, $this->viewPath . '/edit', [
                $this->entityName => array_merge($this->findOrFail($id), $data),
            ]);
        }

        /** @var Schedule|null $schedule */
        $schedule = $result['schedule'] ?? null;

        return $this->updatedResponse($schedule?->toArray() ?? $this->findOrFail($id));
    }

    public function delete(int|string $id): mixed
    {
        $deleted = $this->schedulesService->delete($id, $this->currentUserId());

        if (!$deleted) {
            http_response_code(404);
            throw new RuntimeException('Schedule not found.');
        }

        return $this->deletedResponse();
    }

    public function calendar(int|string|null $id = null): mixed
    {
        return $this->respond('schedules/calendar', [
            'schedule' => $id === null ? null : $this->findOrFail($id),
            'events' => $this->schedulesService->calendar($id === null ? null : (int) $id),
        ]);
    }

    public function attachEvent(int|string $id): mixed
    {
        $data = $this->payload();
        $errors = $this->requireFields($data, ['event_id']);

        if ($errors !== []) {
            return $this->validationResponse($errors, $data, 'schedules/details');
        }

        $result = $this->schedulesService->attachEvent(
            (int) $id,
            (int) $data['event_id'],
            isset($data['assigned_room_id']) && $data['assigned_room_id'] !== '' ? (int) $data['assigned_room_id'] : null,
            (string) ($data['allocation_status'] ?? 'manual'),
            isset($data['conflict_notes']) && $data['conflict_notes'] !== '' ? (string) $data['conflict_notes'] : null,
            $this->currentUserId()
        );

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, 'schedules/details');
        }

        return $this->updatedResponse($this->findOrFail($id));
    }

    protected function domainErrors(array $data): array
    {
        if (!empty($data['starts_on']) && !empty($data['ends_on']) && strtotime((string) $data['ends_on']) < strtotime((string) $data['starts_on'])) {
            return ['ends_on' => 'End date must be on or after start date.'];
        }

        return [];
    }

    protected function listRecords(array $filters = []): array
    {
        return $this->schedulesService->all($filters);
    }

    protected function findRecord(int|string $id): ?array
    {
        return $this->schedulesService->find($id)?->toArray();
    }
}
