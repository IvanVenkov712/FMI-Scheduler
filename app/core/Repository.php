<?php

namespace App\Core;

class Repository
{
    protected $database;
    protected $model_class;
    protected $table;
    protected $table_fields;

    public function __construct(
        $database,
        $model_class,
        $table,
    ) {
        $this->database = $database;
        $this->model_class = $model_class;
        $this->table = $table;
        $this->table_fields = get_class_vars($this->model_class);
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
        $columns = array_keys($payload);
        $values = array_values($payload);

        $placeholders = array_map(fn ($key) => '?', $columns);

        $sql = sprintf(
            "INSERT INTO `%s` (%s) VALUES (%s)",
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $statement = $this->database->connection()->prepare($sql);
        for( $i = 0; $i < count($values); $i++ ) {
            $statement->bindValue($i + 1, $values[$i]);
        }
        $responce = $statement->execute();
        return $responce;
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
            return $this->find_by_id($id);
        }
        $params['id'] = $id;
        $sql = sprintf(
            "UPDATE `%s` SET %s WHERE id = :id",
            $this->table,
            implode(', ', $assignments),
        );

        $statement = $this->database->connection()->prepare($sql);
        $result = $statement->execute($params);
        return $result;
    }

    public function delete($id)
    {
        $statement = $this->database->connection()->prepare(
            "DELETE FROM `{$this->table}` WHERE id = ?"
        );
        $statement->bindValue(1, $id);
        $result = $statement->execute();

        return $result;
    }

    protected function map_row_to_model($row)
    {
        $model = new $this->model_class();
        $data = [];

        foreach ($row as $column => $value) {
            $data[$column] = $value;
        }

        $model->fill($data);

        return $model;
    }
}
