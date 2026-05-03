<?php
namespace App\Repositories;
use App\Core\Repository;
use App\Models\Event;
use App\Models\User;

class UserRepository extends Repository {
    public function __construct($database) {
        parent::__construct($database, User::class, "users");
    }

    public function find_by_username($username) {
        $statement = $this->database->connection()->prepare(
            "SELECT * FROM `{$this->table}` WHERE username = ? LIMIT 1"
        );
        $statement->bindValue(1, $username);
        $statement->execute();

        $row = $statement->fetch();

        return $row === false ? null : $this->map_row_to_model($row);
    }

    public function find_by_email($email) {
        $statement = $this->database->connection()->prepare(
            "SELECT * FROM `{$this->table}` WHERE email = ? LIMIT 1"
        );
        $statement->bindValue(1, $email);
        $statement->execute();

        $row = $statement->fetch();

        return $row === false ? null : $this->map_row_to_model($row);
    }

    public function get_requested_events($id) {
        $statement = $this->database->connection()->prepare(
            "SELECT * FROM `events` WHERE requestor_id = ?"
        );
        $statement->bindValue(1, $id);
        $statement->execute();

        return array_map(
            fn ($row) => new Event($row),
            $statement->fetchAll()
        );
    }

    public function get_id_by_username($username) {
        $statement = $this->database->connection()->prepare(
            "SELECT id FROM `{$this->table}` WHERE username = ? LIMIT 1"
        );
        $statement->bindValue(1, $username);
        $statement->execute();

        $row = $statement->fetch();

        return $row === false ? null : (int) $row["id"];
    }

    public function get_id_by_email($email) {
        $statement = $this->database->connection()->prepare(
            "SELECT id FROM `{$this->table}` WHERE email = ? LIMIT 1"
        );
        $statement->bindValue(1, $email);
        $statement->execute();

        $row = $statement->fetch();

        return $row === false ? null : (int) $row["id"];
    }

    public function get_role($id) {
        $statement = $this->database->connection()->prepare(
            "SELECT `role` FROM `{$this->table}` WHERE id = ? LIMIT 1"
        );
        $statement->bindValue(1, $id);
        $statement->execute();

        $row = $statement->fetch();

        return $row === false ? null : $row["role"];
    }
}