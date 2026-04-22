<?php

declare(strict_types=1);

abstract class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isAssignable($key)) {
                $this->attributes[$key] = $value;
            }
        }

        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function set(string $key, mixed $value): self
    {
        if ($this->isAssignable($key)) {
            $this->attributes[$key] = $value;
        }

        return $this;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function save(): bool
    {
        return (new GenericModelRepository())->save($this);
    }

    public function delete(): bool
    {
        return (new GenericModelRepository())->delete($this);
    }

    public static function find(int|string $id): ?static
    {
        /** @var static|null $model */
        $model = (new GenericModelRepository())->find(static::class, $id);

        return $model;
    }

    public static function all(string $orderBy = 'id', string $direction = 'ASC'): array
    {
        /** @var array<static> $models */
        $models = (new GenericModelRepository())->all(static::class, $orderBy, $direction);

        return $models;
    }

    protected function tableName(): string
    {
        if ($this->table !== '') {
            return $this->table;
        }

        $class = (new ReflectionClass($this))->getShortName();
        $snake = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $class));

        return $snake . 's';
    }

    public function repositoryPersistableAttributes(): array
    {
        return array_filter(
            $this->attributes,
            fn (string $key): bool => $key !== $this->primaryKey,
            ARRAY_FILTER_USE_KEY
        );
    }

    public function repositoryTableName(): string
    {
        return $this->tableName();
    }

    public function repositoryPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function repositoryPrimaryValue(): int|string|null
    {
        return $this->primaryValue();
    }

    public function repositoryAssignPrimaryValue(int|string $value): void
    {
        $this->attributes[$this->primaryKey] = $value;
    }

    protected function isAssignable(string $key): bool
    {
        return $key === $this->primaryKey
            || $this->fillable === []
            || in_array($key, $this->fillable, true);
    }

    protected function exists(): bool
    {
        return isset($this->attributes[$this->primaryKey]);
    }

    protected function primaryValue(): int|string|null
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }
}
