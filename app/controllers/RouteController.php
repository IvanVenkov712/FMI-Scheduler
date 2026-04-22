<?php

declare(strict_types=1);

class RouteController extends CrudController
{
    public function __construct(
        array $request = [],
        private readonly RouteService $routesService = new RouteService()
    ) {
        parent::__construct($request);
    }

    protected string $table = 'routes';
    protected string $viewPath = 'routes';
    protected string $entityName = 'route';
    protected array $fillable = [
        'from_room_id', 'to_room_id', 'from_building_id', 'to_building_id', 'name',
        'description', 'distance_meters', 'walking_duration_minutes', 'car_duration_minutes', 'public_transport_duration_minutes',
        'path_data', 'is_accessible', 'is_bidirectional', 'status',
    ];
    protected array $required = ['name'];
    protected array $integerFields = [
        'from_room_id', 'to_room_id', 'from_building_id', 'to_building_id', 'distance_meters',
        'walking_duration_minutes', 'car_duration_minutes', 'public_transport_duration_minutes',
    ];
    protected array $booleanFields = ['is_accessible', 'is_bidirectional'];
    protected array $jsonFields = ['path_data'];
    protected array $listFilters = ['status', 'is_accessible', 'from_building_id', 'to_building_id'];
    protected string $defaultOrder = 'LEAST(IFNULL(walking_duration_minutes, 65535), IFNULL(car_duration_minutes, 65535), IFNULL(public_transport_duration_minutes, 65535)) ASC, name ASC';

    public function store(): mixed
    {
        $data = $this->payload();
        $result = $this->routesService->create($this->normalize($data), [], $this->currentUserId());

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, $this->viewPath . '/create', $this->formData());
        }

        /** @var Route|null $route */
        $route = $result['route'] ?? null;

        return $this->storedResponse($route?->toArray() ?? []);
    }

    public function update(int|string $id): mixed
    {
        $data = $this->payload();
        $result = $this->routesService->update($id, $this->normalize($data), null, $this->currentUserId());

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, $this->viewPath . '/edit', array_merge($this->formData(), [
                $this->entityName => array_merge($this->findOrFail($id), $data),
            ]));
        }

        /** @var Route|null $route */
        $route = $result['route'] ?? null;

        return $this->updatedResponse($route?->toArray() ?? $this->findOrFail($id));
    }

    public function delete(int|string $id): mixed
    {
        $deleted = $this->routesService->delete($id, $this->currentUserId());

        if (!$deleted) {
            http_response_code(404);
            throw new RuntimeException('Route not found.');
        }

        return $this->deletedResponse();
    }

    public function map(int|string|null $id = null): mixed
    {
        return $this->respond('routes/map', [
            'route' => $id === null ? null : $this->findOrFail($id),
            'routes' => $this->rows(),
            'buildings' => $this->database()->fetchAll('SELECT id, code, name FROM buildings ORDER BY name ASC'),
        ]);
    }

    public function steps(int|string $id): mixed
    {
        return $this->respond('routes/details', [
            'route' => $this->findOrFail($id),
            'steps' => $this->database()->fetchAll(
                'SELECT * FROM route_steps WHERE route_id = :route_id ORDER BY step_order ASC',
                ['route_id' => $id]
            ),
        ]);
    }

    public function saveSteps(int|string $id): mixed
    {
        $steps = (array) ($this->payload()['steps'] ?? []);
        $this->routesService->saveSteps((int) $id, $steps);

        return $this->updatedResponse($this->findOrFail($id));
    }

    protected function formData(): array
    {
        return [
            'rooms' => $this->database()->fetchAll('SELECT id, code, name FROM rooms ORDER BY code ASC'),
            'buildings' => $this->database()->fetchAll('SELECT id, code, name FROM buildings ORDER BY name ASC'),
        ];
    }

    protected function validate(array $data, bool $partial = false): array
    {
        $fields = $partial ? array_intersect($this->required, array_keys($data)) : $this->required;
        $errors = $this->requireFields($data, $fields);

        return array_merge($errors, $this->routeDomainErrors($data, $partial));
    }

    private function routeDomainErrors(array $data, bool $partial): array
    {
        $errors = [];
        $hasStartFields = array_key_exists('from_room_id', $data) || array_key_exists('from_building_id', $data);
        $hasEndFields = array_key_exists('to_room_id', $data) || array_key_exists('to_building_id', $data);

        if ((!$partial || $hasStartFields) && empty($data['from_room_id']) && empty($data['from_building_id'])) {
            $errors['from'][] = 'A route must define a start room or start building.';
        }

        if ((!$partial || $hasEndFields) && empty($data['to_room_id']) && empty($data['to_building_id'])) {
            $errors['to'][] = 'A route must define an end room or end building.';
        }

        $durationFields = [
            'walking_duration_minutes',
            'car_duration_minutes',
            'public_transport_duration_minutes',
        ];

        $hasDuration = false;
        $durationFieldPresent = false;

        foreach ($durationFields as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === '' || $data[$field] === null) {
                continue;
            }

            $durationFieldPresent = true;

            if ((int) $data[$field] < 1) {
                $errors[$field][] = 'Travel duration must be at least 1 minute.';
                continue;
            }

            $hasDuration = true;
        }

        if ((!$partial || $durationFieldPresent) && !$hasDuration) {
            $errors['durations'][] = 'At least one of the walking, car, or public transport durations is required.';
        }

        return $errors;
    }
}
