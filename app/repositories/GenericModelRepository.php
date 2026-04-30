<?php

declare(strict_types=1);

class GenericModelRepository
{
    protected readonly ?Database $database;

    public function __construct(
        ?Database $database = null
    ) {
        $this->database = $database;
    }

    protected function saveModel(Model $model): bool
    {
        $data = $model->repositoryPersistableAttributes();
        $primaryValue = $model->repositoryPrimaryValue();

        if ($primaryValue !== null) {
            return $this->update($model, $primaryValue, $data);
        }

        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);
        $id = $this->db()->insert(
            sprintf(
                'INSERT INTO %s (%s) VALUES (%s)',
                $model->repositoryTableName(),
                implode(', ', $columns),
                implode(', ', $placeholders)
            ),
            $data
        );

        $model->repositoryAssignPrimaryValue(is_numeric($id) ? (int) $id : $id);

        return true;
    }

    protected function deleteModel(Model $model): bool
    {
        $primaryValue = $model->repositoryPrimaryValue();
        if ($primaryValue === null) {
            return false;
        }

        $count = $this->db()->execute(
            sprintf(
                'DELETE FROM %s WHERE %s = :id',
                $model->repositoryTableName(),
                $model->repositoryPrimaryKey()
            ),
            ['id' => $primaryValue]
        );

        return $count > 0;
    }

    protected function findModel(string $modelClass, int|string $id): ?object
    {
        /** @var Model $model */
        $model = new $modelClass();
        $row = $this->db()->fetch(
            sprintf(
                'SELECT * FROM %s WHERE %s = :id LIMIT 1',
                $model->repositoryTableName(),
                $model->repositoryPrimaryKey()
            ),
            ['id' => $id]
        );

        return $row === null ? null : new $modelClass($row);
    }

    protected function allModels(string $modelClass, string $orderBy = 'id', string $direction = 'ASC'): array
    {
        /** @var Model $model */
        $model = new $modelClass();
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $rows = $this->db()->fetchAll(
            sprintf(
                'SELECT * FROM %s ORDER BY %s %s',
                $model->repositoryTableName(),
                $orderBy,
                $direction
            )
        );

        return array_map(static fn (array $row): object => new $modelClass($row), $rows);
    }

    private function update(Model $model, int|string $id, array $data): bool
    {
        if ($data === []) {
            return true;
        }

        $assignments = array_map(static fn (string $column): string => "{$column} = :{$column}", array_keys($data));
        $data['model_id'] = $id;

        $count = $this->db()->execute(
            sprintf(
                'UPDATE %s SET %s WHERE %s = :model_id',
                $model->repositoryTableName(),
                implode(', ', $assignments),
                $model->repositoryPrimaryKey()
            ),
            $data
        );

        return $count >= 0;
    }

    protected function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }
}

