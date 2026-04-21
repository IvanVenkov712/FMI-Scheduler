<?php

declare(strict_types=1);

class Building extends Model
{
    protected string $table = 'buildings';
    protected array $fillable = [
        'code',
        'name',
        'address',
        'description',
        'floors_count',
        'orientation',
        'schematic_description',
        'vertical_view_east',
        'vertical_view_west',
        'vertical_view_north',
        'vertical_view_south',
        'status',
    ];

    public function floors(): array
    {
        if (!$this->exists()) {
            return [];
        }

        $rows = $this->db()->fetchAll(
            'SELECT * FROM floors WHERE building_id = :building_id ORDER BY number ASC',
            ['building_id' => $this->primaryValue()]
        );

        return array_map(static fn (array $row): Floor => new Floor($row), $rows);
    }

    public function activeFloors(): array
    {
        return array_values(array_filter(
            $this->floors(),
            static fn (Floor $floor): bool => $floor->isActive()
        ));
    }

    public function routesFrom(): array
    {
        return $this->routeCollection('from_building_id');
    }

    public function routesTo(): array
    {
        return $this->routeCollection('to_building_id');
    }

    public function isActive(): bool
    {
        return $this->get('status', 'active') === 'active';
    }

    public function verticalView(string $orientation = ''): ?string
    {
        $orientation = $orientation !== '' ? $orientation : (string) $this->get('orientation', 'east');
        $allowed = ['east', 'west', 'north', 'south'];

        if (!in_array($orientation, $allowed, true)) {
            return null;
        }

        return $this->get('vertical_view_' . $orientation)
            ?? $this->get('schematic_description');
    }

    private function routeCollection(string $column): array
    {
        if (!$this->exists()) {
            return [];
        }

        $rows = $this->db()->fetchAll(
            "SELECT * FROM routes
             WHERE {$column} = :building_id
             ORDER BY LEAST(
                IFNULL(walking_duration_minutes, 65535),
                IFNULL(car_duration_minutes, 65535),
                IFNULL(public_transport_duration_minutes, 65535)
             ) ASC, name ASC",
            ['building_id' => $this->primaryValue()]
        );

        return array_map(static fn (array $row): Route => new Route($row), $rows);
    }
}
