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
        return $this->exists() ? (new BuildingRepository())->floors((int) $this->primaryValue()) : [];
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
        return $this->exists() ? (new BuildingRepository())->routesForBuilding((int) $this->primaryValue(), $column) : [];
    }
}
