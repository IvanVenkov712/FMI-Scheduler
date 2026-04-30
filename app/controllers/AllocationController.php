<?php

declare(strict_types=1);

class AllocationController extends Controller
{
    public function __construct(
        array $request = [],
        private readonly AllocationService $allocation = new AllocationService()
    ) {
        parent::__construct($request);
    }

    public function search(): mixed
    {
        $searchInput = [
            'capacity' => (int) $this->input('capacity', 0),
            'starts_at' => (string) $this->input('starts_at', ''),
            'ends_at' => (string) $this->input('ends_at', ''),
            'delivery_mode' => (string) $this->input('delivery_mode', 'physical'),
            'resource_requirements' => $this->input('resource_requirements', $this->input('resource_ids', $this->input('resources', []))),
            'resource_quantities' => $this->input('resource_quantities', []),
        ];
        $resourceRequirements = $this->allocation->searchResourceRequirements($searchInput);
        $rooms = $this->allocation->searchRoomOptions($searchInput);

        return $this->respond('allocation/search', [
            'rooms' => $rooms,
            'filters' => [
                'capacity' => $searchInput['capacity'],
                'startsAt' => $searchInput['starts_at'],
                'endsAt' => $searchInput['ends_at'],
                'deliveryMode' => $searchInput['delivery_mode'],
                'resourceIds' => array_column($resourceRequirements, 'resource_id'),
                'resourceRequirements' => $resourceRequirements,
            ],
        ]);
    }

    public function autoAssign(int|string|null $scheduleId = null): mixed
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            $scheduleId = $this->normalizeScheduleId($scheduleId ?? $this->input('schedule_id'));

            return $this->respond('allocation/auto-assign', [
                'scheduleId' => $scheduleId,
            ]);
        }

        $data = $this->body() + $this->input();
        $scheduleId = $this->normalizeScheduleId($scheduleId ?? $data['schedule_id'] ?? null);
        $allocation = $this->allocation->allocateSchedule($scheduleId, $data, $this->currentUserId());
        $runId = $allocation['run_id'] ?? null;
        $result = $allocation['result'] ?? [];

        if ($runId === null) {
            throw new RuntimeException('Allocation run could not be created.');
        }

        return $this->respond('allocation/result', [
            'run' => $this->run($runId),
            'result' => $result,
        ]);
    }

    public function result(int|string $id): mixed
    {
        $run = $this->run($id);

        return $this->respond('allocation/result', [
            'run' => $run,
            'result' => json_decode((string) ($run['result_data'] ?? '{}'), true) ?: [],
        ]);
    }

    private function run(int|string $id): array
    {
        $run = $this->allocation->run($id);

        if ($run === null) {
            throw new RuntimeException('Allocation run not found.');
        }

        return $run;
    }

    private function respond(string $view, array $data): mixed
    {
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || $this->input('format') === 'json') {
            return $this->sanitize($data);
        }

        return $this->render($view, $this->sanitize($data));
    }

    private function normalizeScheduleId(mixed $scheduleId): ?int
    {
        if ($scheduleId === null || $scheduleId === '') {
            return null;
        }

        $scheduleId = (int) $scheduleId;

        return $scheduleId > 0 ? $scheduleId : null;
    }

    private function currentUserId(): ?int
    {
        return isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
    }
}
