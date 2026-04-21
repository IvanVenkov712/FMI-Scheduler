<?php

declare(strict_types=1);

class FloorController extends CrudController
{
    public function __construct(
        array $request = [],
        private readonly FloorService $floorsService = new FloorService()
    ) {
        parent::__construct($request);
    }

    protected string $table = 'floors';
    protected string $viewPath = 'floors';
    protected string $entityName = 'floor';
    protected array $fillable = [
        'building_id', 'number', 'name', 'description', 'plan_data', 'ascii_plan',
        'capacity', 'width_units', 'height_units', 'status',
    ];
    protected array $required = ['building_id', 'number', 'name'];
    protected array $integerFields = ['building_id', 'number', 'capacity', 'width_units', 'height_units'];
    protected array $listFilters = ['building_id', 'status'];
    protected string $defaultOrder = 'building_id ASC, number ASC';

    public function store(): mixed
    {
        $data = $this->payload();
        $result = $this->floorsService->create($this->normalize($data), $this->currentUserId());

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, $this->viewPath . '/create', $this->formData());
        }

        /** @var Floor|null $floor */
        $floor = $result['floor'] ?? null;

        return $this->storedResponse($floor?->toArray() ?? []);
    }

    public function update(int|string $id): mixed
    {
        $data = $this->payload();
        $result = $this->floorsService->update($id, $this->normalize($data), $this->currentUserId());

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, $this->viewPath . '/edit', array_merge($this->formData(), [
                $this->entityName => array_merge($this->findOrFail($id), $data),
            ]));
        }

        /** @var Floor|null $floor */
        $floor = $result['floor'] ?? null;

        return $this->updatedResponse($floor?->toArray() ?? $this->findOrFail($id));
    }

    public function delete(int|string $id): mixed
    {
        $deleted = $this->floorsService->delete($id, $this->currentUserId());

        if (!$deleted) {
            http_response_code(404);
            throw new RuntimeException('Floor not found.');
        }

        return $this->deletedResponse();
    }

    public function rooms(int|string $id): mixed
    {
        return $this->respond('rooms/index', [
            'floor' => $this->findOrFail($id),
            'rooms' => $this->rows('SELECT * FROM rooms WHERE floor_id = :id ORDER BY code ASC', ['id' => $id]),
        ]);
    }

    public function plan(int|string $id): mixed
    {
        return $this->respond('floors/edit', [
            'floor' => $this->findOrFail($id),
            'rooms' => $this->rows('SELECT * FROM rooms WHERE floor_id = :id ORDER BY code ASC', ['id' => $id]),
            'points' => $this->rows('SELECT * FROM floor_points WHERE floor_id = :id ORDER BY type ASC, code ASC', ['id' => $id]),
        ]);
    }

    public function updatePlan(int|string $id): mixed
    {
        $data = $this->payload();
        $asciiPlan = (string) ($data['ascii_plan'] ?? '');
        $planData = array_key_exists('plan_data', $data) ? $this->jsonValue($data['plan_data']) : null;
        $points = isset($data['points']) && is_array($data['points']) ? $data['points'] : [];

        $result = $this->floorsService->savePlan((int) $id, $asciiPlan, $points, $planData, $this->currentUserId());
        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, 'floors/edit', [
                'floor' => array_merge($this->findOrFail($id), $data),
                'rooms' => $this->rows('SELECT * FROM rooms WHERE floor_id = :id ORDER BY code ASC', ['id' => $id]),
                'points' => $points,
            ]);
        }

        /** @var Floor|null $floor */
        $floor = $result['floor'] ?? null;

        return $this->updatedResponse($floor?->toArray() ?? $this->findOrFail($id));
    }

    protected function formData(): array
    {
        return [
            'buildings' => $this->database()->fetchAll('SELECT id, name, code FROM buildings ORDER BY name ASC'),
        ];
    }
}
