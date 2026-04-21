<?php

declare(strict_types=1);

class BuildingController extends CrudController
{
    public function __construct(
        array $request = [],
        private readonly BuildingService $buildings = new BuildingService()
    ) {
        parent::__construct($request);
    }

    protected string $table = 'buildings';
    protected string $viewPath = 'buildings';
    protected string $entityName = 'building';
    protected array $fillable = [
        'code', 'name', 'address', 'description', 'floors_count', 'orientation',
        'schematic_description', 'vertical_view_east', 'vertical_view_west',
        'vertical_view_north', 'vertical_view_south', 'status',
    ];
    protected array $required = ['code', 'name'];
    protected array $integerFields = ['floors_count'];
    protected array $listFilters = ['status', 'orientation'];
    protected string $defaultOrder = 'name ASC';

    public function store(): mixed
    {
        $data = $this->payload();
        $result = $this->buildings->create($this->normalize($data), $this->currentUserId());

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, $this->viewPath . '/create');
        }

        /** @var Building|null $building */
        $building = $result['building'] ?? null;

        return $this->storedResponse($building?->toArray() ?? []);
    }

    public function update(int|string $id): mixed
    {
        $data = $this->payload();
        $result = $this->buildings->update($id, $this->normalize($data), $this->currentUserId());

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, $this->viewPath . '/edit', [
                $this->entityName => array_merge($this->findOrFail($id), $data),
            ]);
        }

        /** @var Building|null $building */
        $building = $result['building'] ?? null;

        return $this->updatedResponse($building?->toArray() ?? $this->findOrFail($id));
    }

    public function delete(int|string $id): mixed
    {
        $deleted = $this->buildings->delete($id, $this->currentUserId());

        if (!$deleted) {
            http_response_code(404);
            throw new RuntimeException('Building not found.');
        }

        return $this->deletedResponse();
    }

    public function floors(int|string $id): mixed
    {
        return $this->respond('floors/index', [
            'building' => $this->findOrFail($id),
            'floors' => $this->rows('SELECT * FROM floors WHERE building_id = :id ORDER BY number ASC', ['id' => $id]),
        ]);
    }

    public function verticalView(int|string $id, string $orientation = 'east'): mixed
    {
        $allowed = ['east', 'west', 'north', 'south'];
        $orientation = in_array($orientation, $allowed, true) ? $orientation : 'east';
        $building = $this->findOrFail($id);

        return $this->respond('buildings/details', [
            'building' => $building,
            'orientation' => $orientation,
            'vertical_view' => $building['vertical_view_' . $orientation] ?? $building['schematic_description'] ?? '',
        ]);
    }
}
