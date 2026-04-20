<?php

declare(strict_types=1);

class BuildingController extends CrudController
{
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
