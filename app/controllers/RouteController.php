<?php

declare(strict_types=1);

class RouteController extends CrudController
{
    protected string $table = 'routes';
    protected string $viewPath = 'routes';
    protected string $entityName = 'route';
    protected array $fillable = [
        'from_room_id', 'to_room_id', 'from_building_id', 'to_building_id', 'name',
        'description', 'movement_type', 'distance_meters', 'duration_minutes',
        'path_data', 'is_accessible', 'status',
    ];
    protected array $required = ['name', 'movement_type', 'duration_minutes'];
    protected array $integerFields = ['from_room_id', 'to_room_id', 'from_building_id', 'to_building_id', 'distance_meters', 'duration_minutes'];
    protected array $booleanFields = ['is_accessible'];
    protected array $jsonFields = ['path_data'];
    protected array $listFilters = ['movement_type', 'status', 'from_building_id', 'to_building_id'];
    protected string $defaultOrder = 'duration_minutes ASC, name ASC';

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

        $this->database()->transaction(function (Database $db) use ($id, $steps): void {
            $db->execute('DELETE FROM route_steps WHERE route_id = :route_id', ['route_id' => $id]);

            foreach ($steps as $index => $step) {
                if (!is_array($step) || empty($step['instruction'])) {
                    continue;
                }

                $db->insert(
                    'INSERT INTO route_steps (route_id, step_order, instruction, point_type, room_id, floor_point_id, building_id, duration_minutes)
                     VALUES (:route_id, :step_order, :instruction, :point_type, :room_id, :floor_point_id, :building_id, :duration_minutes)',
                    [
                        'route_id' => $id,
                        'step_order' => (int) ($step['step_order'] ?? ($index + 1)),
                        'instruction' => $step['instruction'],
                        'point_type' => $step['point_type'] ?? 'text',
                        'room_id' => $step['room_id'] ?? null,
                        'floor_point_id' => $step['floor_point_id'] ?? null,
                        'building_id' => $step['building_id'] ?? null,
                        'duration_minutes' => (int) ($step['duration_minutes'] ?? 0),
                    ]
                );
            }
        });

        return $this->updatedResponse($this->findOrFail($id));
    }

    protected function formData(): array
    {
        return [
            'rooms' => $this->database()->fetchAll('SELECT id, code, name FROM rooms ORDER BY code ASC'),
            'buildings' => $this->database()->fetchAll('SELECT id, code, name FROM buildings ORDER BY name ASC'),
        ];
    }
}
