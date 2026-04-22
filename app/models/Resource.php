<?php

declare(strict_types=1);

class Resource extends Model
{
    protected string $table = 'resources';
    protected array $fillable = [
        'name',
        'slug',
        'description',
        'unit',
        'is_consumable',
    ];

    public function roomResources(): array
    {
        return $this->exists() ? (new ResourceRepository())->roomResourceModels((int) $this->primaryValue()) : [];
    }

    public function rooms(): array
    {
        return $this->exists() ? (new ResourceRepository())->roomsForResource((int) $this->primaryValue()) : [];
    }

    public function requests(): array
    {
        return $this->exists() ? (new ResourceRepository())->resourceRequests(['resource_id' => (int) $this->primaryValue()]) : [];
    }

    public function isConsumable(): bool
    {
        return (int) $this->get('is_consumable', 0) === 1;
    }
}
