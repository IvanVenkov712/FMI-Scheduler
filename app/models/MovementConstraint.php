<?php

declare(strict_types=1);

class MovementConstraint extends Model
{
    protected string $table = 'movement_constraints';
    protected array $fillable = [
        'name',
        'type',
        'scope',
        'building_id',
        'floor_id',
        'room_id',
        'route_id',
        'rule_data',
        'description',
        'starts_at',
        'ends_at',
        'is_active',
        'created_by',
    ];

    public function building(): ?Building
    {
        $buildingId = $this->get('building_id');

        return $buildingId === null ? null : Building::find((int) $buildingId);
    }

    public function floor(): ?Floor
    {
        $floorId = $this->get('floor_id');

        return $floorId === null ? null : Floor::find((int) $floorId);
    }

    public function room(): ?Room
    {
        $roomId = $this->get('room_id');

        return $roomId === null ? null : Room::find((int) $roomId);
    }

    public function route(): ?Route
    {
        $routeId = $this->get('route_id');

        return $routeId === null ? null : Route::find((int) $routeId);
    }

    public function creator(): ?User
    {
        $createdBy = $this->get('created_by');

        return $createdBy === null ? null : User::find((int) $createdBy);
    }

    public function ruleData(): array
    {
        $value = $this->get('rule_data');

        if (!is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function isCurrentlyActive(?DateTimeInterface $at = null): bool
    {
        if ((int) $this->get('is_active', 1) !== 1) {
            return false;
        }

        $at ??= new DateTimeImmutable();
        $start = $this->get('starts_at');
        $end = $this->get('ends_at');

        if (is_string($start) && $start !== '' && $at < new DateTimeImmutable($start)) {
            return false;
        }

        if (is_string($end) && $end !== '' && $at > new DateTimeImmutable($end)) {
            return false;
        }

        return true;
    }

    public function appliesTo(string $scope): bool
    {
        return $this->get('scope', 'global') === $scope;
    }
}
