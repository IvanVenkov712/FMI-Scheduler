<?php

declare(strict_types=1);

class RoomType extends Model
{
    protected string $table = 'room_types';
    protected array $fillable = [
        'name',
        'slug',
        'description',
        'is_physical',
        'supports_online',
    ];

    public function rooms(): array
    {
        return $this->exists() ? (new RoomTypeRepository())->rooms((int) $this->primaryValue()) : [];
    }

    public function isPhysical(): bool
    {
        return (int) $this->get('is_physical', 1) === 1;
    }

    public function supportsOnline(): bool
    {
        return (int) $this->get('supports_online', 0) === 1;
    }

    public function isHybrid(): bool
    {
        return $this->isPhysical() && $this->supportsOnline();
    }
}
