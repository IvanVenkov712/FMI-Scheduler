<?php

namespace App\Core;
use \InvalidArgumentException;
use \App\Exceptions\InsertException;
use \Throwable;

class Repository
{
    protected $database;
    protected $model_class;
    protected $table;
    protected $table_fields;

    public function __construct($database = null, $model_class = null, $table = null) {
        $this->database = $database ?? new Database();
        $this->model_class = $model_class ?? $this->model_class;
        $this->table = $table ?? $this->table;
        $this->table_fields = array_keys(get_class_vars($this->model_class));
        if(!in_array('id', $this->table_fields)){
            throw new InvalidArgumentException('The base repository works under the assumption there is id field');
        }
    }

    public function find_all()
    {
        $statement = $this->database->connection()->prepare("SELECT * FROM `{$this->table}`");
        $statement->execute();

        return array_map(
            fn ($row) => $this->map_row_to_model($row),
            $statement->fetchAll()
        );
    }

    public function exists($id) {
        $statement = $this->database->connection()->prepare(
            "SELECT 1 FROM `{$this->table}` WHERE id = ? LIMIT 1"
        );
        $statement->bindValue(1, $id);
        $statement->execute();

        return $statement->fetchColumn() !== false;
    }

    public function find_by_id($id)
    {
        $statement = $this->database->connection()->prepare(
            "SELECT * FROM `{$this->table}` WHERE id = ? LIMIT 1"
        );
        $statement->bindValue(1, $id);
        $statement->execute();

        $row = $statement->fetch();

        return $row === false ? null : $this->map_row_to_model($row);
    }

    public function insert($model)
    {
        $payload = $model->to_array();
        unset($payload["id"]);
        $columns = array_map(fn ($column) => "`$column`", array_keys($payload));
        $values = array_values($payload);

        $placeholders = array_map(fn ($key) => '?', $columns);

        $sql = sprintf(
            "INSERT INTO `%s` (%s) VALUES (%s)",
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        try {
            $statement = $this->database->connection()->prepare($sql);
            for( $i = 0; $i < count($values); $i++ ) {
                $statement->bindValue($i + 1, $values[$i]);
            }
            $statement->execute();
        } catch (Throwable $exception) {
            throw new InsertException("Problem occured while inserting.");
        }

        return $this->database->connection()->lastInsertId();
    }

    public function update($id, $model)
    {
        $payload = $model->to_array();
        $assignments = [];
        $params = [];

        foreach ($payload as $column => $value) {
            if ($column === 'id') {
                continue;
            }

            $assignments[] = "`{$column}` = :set_{$column}";
            $params["set_{$column}"] = $value;
        }

        if ($assignments === []) {
            return false;
        }
        $params['id'] = $id;
        $sql = sprintf(
            "UPDATE `%s` SET %s WHERE id = :id",
            $this->table,
            implode(', ', $assignments),
        );

        $statement = $this->database->connection()->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount() > 0;
    }

    public function delete($id)
    {
        $statement = $this->database->connection()->prepare(
            "DELETE FROM `{$this->table}` WHERE id = ?"
        );
        $statement->bindValue(1, $id);
        $statement->execute();

        return $statement->rowCount() > 0;
    }

    public function begin_transaction()
    {
        $connection = $this->database->connection();
        return !$connection->inTransaction() && $connection->beginTransaction();
    }

    public function commit()
    {
        $connection = $this->database->connection();
        return $connection->inTransaction() && $connection->commit();
    }

    public function roll_back()
    {
        $connection = $this->database->connection();
        return $connection->inTransaction() && $connection->rollBack();
    }

    protected function map_row_to_model($row)
    {
        $model = new $this->model_class();
        $model->fill($row);
        return $model;
    }

    protected function rows($sql, $params = [], $class = null)
    {
        return array_map(
            fn ($row) => $class === null ? $this->map_row_to_model($row) : new $class($row),
            $this->run($sql, $params)->fetchAll()
        );
    }

    protected function row($sql, $params = [], $class = null)
    {
        $row = $this->run($sql, $params)->fetch();

        return $row === false ? null : ($class === null ? $this->map_row_to_model($row) : new $class($row));
    }

    protected function value($sql, $params = [])
    {
        $value = $this->run($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    protected function run($sql, $params = [])
    {
        $statement = $this->database->connection()->prepare($sql);
        $statement->execute($params);

        return $statement;
    }
}
