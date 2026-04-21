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
            if ($this->fillable === [] || in_array($key, $this->fillable, true)) {
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
        if ($this->fillable === [] || in_array($key, $this->fillable, true)) {
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
        $data = $this->persistableAttributes();

        if (isset($this->attributes[$this->primaryKey])) {
            return $this->updateById($this->attributes[$this->primaryKey], $data);
        }

        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->tableName(),
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $id = Database::getInstance(Database::configFromFile())->insert($sql, $data);
        $this->attributes[$this->primaryKey] = is_numeric($id) ? (int) $id : $id;

        return true;
    }

    public function delete(): bool
    {
        if (!isset($this->attributes[$this->primaryKey])) {
            return false;
        }

        $count = Database::getInstance(Database::configFromFile())->execute(
            sprintf('DELETE FROM %s WHERE %s = :id', $this->tableName(), $this->primaryKey),
            ['id' => $this->attributes[$this->primaryKey]]
        );

        return $count > 0;
    }

    public static function find(int|string $id): ?static
    {
        $model = new static();
        $row = Database::getInstance(Database::configFromFile())->fetch(
            sprintf('SELECT * FROM %s WHERE %s = :id LIMIT 1', $model->tableName(), $model->primaryKey),
            ['id' => $id]
        );

        return $row === null ? null : new static($row);
    }

    public static function all(string $orderBy = 'id', string $direction = 'ASC'): array
    {
        $model = new static();
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $rows = Database::getInstance(Database::configFromFile())->fetchAll(
            sprintf('SELECT * FROM %s ORDER BY %s %s', $model->tableName(), $orderBy, $direction)
        );

        return array_map(static fn (array $row): static => new static($row), $rows);
    }

    protected function updateById(int|string $id, array $data): bool
    {
        if ($data === []) {
            return true;
        }

        $assignments = array_map(static fn (string $column): string => "{$column} = :{$column}", array_keys($data));
        $data['model_id'] = $id;

        $count = Database::getInstance(Database::configFromFile())->execute(
            sprintf(
                'UPDATE %s SET %s WHERE %s = :model_id',
                $this->tableName(),
                implode(', ', $assignments),
                $this->primaryKey
            ),
            $data
        );

        return $count >= 0;
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

    protected function persistableAttributes(): array
    {
        return array_filter(
            $this->attributes,
            fn (string $key): bool => $key !== $this->primaryKey,
            ARRAY_FILTER_USE_KEY
        );
    }

    protected function db(): Database
    {
        return Database::getInstance(Database::configFromFile());
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
