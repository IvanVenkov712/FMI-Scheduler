<?php

namespace App\Core;

use PDO;
use InvalidArgumentException;

class Repository
{
    protected $database;
    protected string $modelClass;
    protected string $table;
    protected array $primaryKeys;

    public function __construct(
        $database,
        $modelClass,
        $table = null,
        $primaryKeys = 'id'
    ) {
        $this->database = $database;
        $this->modelClass = $modelClass;
        $this->table = $table ?? $this->resolveTableName($modelClass);
        $this->primaryKeys = (array) $primaryKeys;
    }

    public function findAll()
    {
        $statement = $this->database->connection()->prepare("SELECT * FROM `{$this->table}`");
        $statement->execute();

        return array_map(
            fn (array $row) => $this->mapRowToModel($row),
            $statement->fetchAll()
        );
    }

    public function findById($id)
    {
        [$whereClause, $params] = $this->buildPrimaryKeyWhereClause($id);

        $statement = $this->database->connection()->prepare(
            "SELECT * FROM `{$this->table}` WHERE {$whereClause} LIMIT 1"
        );
        $statement->execute($params);

        $row = $statement->fetch();

        return $row === false ? null : $this->mapRowToModel($row);
    }

    public function create($data)
    {
        $payload = $this->normalizeData($data);
        $columns = [];
        $placeholders = [];
        $params = [];

        foreach ($payload as $property => $value) {
            $column = $this->propertyToColumn($property);

            if (
                count($this->primaryKeys) === 1 &&
                $column === $this->primaryKeys[0] &&
                ($value === null || $value === 0 || $value === '0' || $value === '')
            ) {
                continue;
            }

            $columns[] = "`{$column}`";
            $placeholders[] = ":{$column}";
            $params[$column] = $value;
        }

        $sql = sprintf(
            "INSERT INTO `%s` (%s) VALUES (%s)",
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $statement = $this->database->connection()->prepare($sql);
        $statement->execute($params);

        if (count($this->primaryKeys) === 1) {
            $primaryKey = $this->primaryKeys[0];
            $primaryProperty = $this->columnToProperty($primaryKey);

            if (!isset($payload[$primaryProperty]) || !$payload[$primaryProperty]) {
                $payload[$primaryProperty] = (int) $this->database->connection()->lastInsertId();
            }

            return $this->findById($payload[$primaryProperty]) ?? $this->mapRowToModel(
                $this->convertPropertiesToColumns($payload)
            );
        }

        return $this->mapRowToModel($this->convertPropertiesToColumns($payload));
    }

    public function update($id, $data)
    {
        $payload = $this->normalizeData($data);
        $assignments = [];
        $params = [];

        foreach ($payload as $property => $value) {
            $column = $this->propertyToColumn($property);

            if (in_array($column, $this->primaryKeys, true)) {
                continue;
            }

            $assignments[] = "`{$column}` = :set_{$column}";
            $params["set_{$column}"] = $value;
        }

        if ($assignments === []) {
            return $this->findById($id);
        }

        [$whereClause, $whereParams] = $this->buildPrimaryKeyWhereClause($id, 'pk_');
        $params = array_merge($params, $whereParams);

        $sql = sprintf(
            "UPDATE `%s` SET %s WHERE %s",
            $this->table,
            implode(', ', $assignments),
            $whereClause
        );

        $statement = $this->database->connection()->prepare($sql);
        $statement->execute($params);

        return $this->findById($id);
    }

    public function delete($id)
    {
        [$whereClause, $params] = $this->buildPrimaryKeyWhereClause($id);

        $statement = $this->database->connection()->prepare(
            "DELETE FROM `{$this->table}` WHERE {$whereClause}"
        );
        $statement->execute($params);

        return $statement->rowCount() > 0;
    }

    protected function mapRowToModel($row)
    {
        $model = new $this->modelClass();
        $data = [];

        foreach ($row as $column => $value) {
            $property = property_exists($model, $column)
                ? $column
                : $this->columnToProperty($column);

            $data[$property] = $value;
        }

        $model->fill($data);

        return $model;
    }

    protected function normalizeData($data): array
    {
        if ($data instanceof Model) {
            return $data->toArray();
        }

        $model = new $this->modelClass();
        $model->fill($data);

        return $model->toArray();
    }

    protected function buildPrimaryKeyWhereClause(int|string|array $id, string $prefix = ''): array
    {
        $ids = is_array($id) ? $id : [$this->columnToProperty($this->primaryKeys[0]) => $id];
        $clauses = [];
        $params = [];

        foreach ($this->primaryKeys as $primaryKey) {
            $property = $this->columnToProperty($primaryKey);

            if (!array_key_exists($primaryKey, $ids) && !array_key_exists($property, $ids)) {
                throw new InvalidArgumentException("Missing value for primary key '{$primaryKey}'.");
            }

            $value = $ids[$primaryKey] ?? $ids[$property];
            $parameter = "{$prefix}{$primaryKey}";

            $clauses[] = "`{$primaryKey}` = :{$parameter}";
            $params[$parameter] = $value;
        }

        return [implode(' AND ', $clauses), $params];
    }

    protected function resolveTableName(string $modelClass): string
    {
        $parts = explode('\\', $modelClass);
        $className = end($parts);
        $snakeCase = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $className));

        return match ($snakeCase) {
            'online_access' => 'online_access',
            default => str_ends_with($snakeCase, 's') ? $snakeCase : "{$snakeCase}s",
        };
    }

    protected function propertyToColumn(string $property): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $property));
    }

    protected function columnToProperty(string $column): string
    {
        $camelCase = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $column))));

        $model = new $this->modelClass();

        if (property_exists($model, $column)) {
            return $column;
        }

        return property_exists($model, $camelCase) ? $camelCase : $column;
    }

    protected function convertPropertiesToColumns(array $data): array
    {
        $result = [];

        foreach ($data as $property => $value) {
            $result[$this->propertyToColumn($property)] = $value;
        }

        return $result;
    }
}
