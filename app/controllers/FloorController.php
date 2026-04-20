<?php

declare(strict_types=1);

class FloorController extends CrudController
{
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
        $this->updateRow($id, $this->normalize($data));

        if (isset($data['points']) && is_array($data['points'])) {
            $this->replacePoints($id, $data['points']);
        }

        return $this->updatedResponse($this->findOrFail($id));
    }

    protected function formData(): array
    {
        return [
            'buildings' => $this->database()->fetchAll('SELECT id, name, code FROM buildings ORDER BY name ASC'),
        ];
    }

    private function replacePoints(int|string $floorId, array $points): void
    {
        $this->database()->transaction(function (Database $db) use ($floorId, $points): void {
            $db->execute('DELETE FROM floor_points WHERE floor_id = :floor_id', ['floor_id' => $floorId]);

            foreach ($points as $point) {
                if (!is_array($point) || empty($point['code']) || empty($point['name']) || empty($point['type'])) {
                    continue;
                }

                $db->insert(
                    'INSERT INTO floor_points (floor_id, code, name, type, x, y, is_accessible)
                     VALUES (:floor_id, :code, :name, :type, :x, :y, :is_accessible)',
                    [
                        'floor_id' => $floorId,
                        'code' => $point['code'],
                        'name' => $point['name'],
                        'type' => $point['type'],
                        'x' => (int) ($point['x'] ?? 0),
                        'y' => (int) ($point['y'] ?? 0),
                        'is_accessible' => empty($point['is_accessible']) ? 0 : 1,
                    ]
                );
            }
        });
    }
}
