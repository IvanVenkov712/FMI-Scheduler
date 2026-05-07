<?php
namespace App\Repositories;
use App\Core\Repository;
use App\Models\Event;
use App\Models\User;

class UserRepository extends Repository {
    protected $model_class = User::class;
    protected $table = 'users';

    public function find_by_username($username) {
        return $this->row("SELECT * FROM `{$this->table}` WHERE username = ? LIMIT 1", [$username]);
    }

    public function find_by_email($email) {
        return $this->row("SELECT * FROM `{$this->table}` WHERE email = ? LIMIT 1", [$email]);
    }

    public function find_user_by_identity($identity)
    {
        if ($identity === null) {
            return null;
        }

        $identity = trim((string) $identity);
        if ($identity === '') {
            return null;
        }

        if (ctype_digit($identity)) {
            return $this->find_by_id((int) $identity);
        }

        if (filter_var($identity, FILTER_VALIDATE_EMAIL)) {
            return $this->find_by_email(strtolower($identity));
        }
        return $this->find_by_username($identity);
    }

    public function get_requested_events($id) {
        return $this->rows("SELECT * FROM `events` WHERE requestor_id = ?", [$id], Event::class);
    }

    public function get_id_by_username($username) {
        $id = $this->value("SELECT id FROM `{$this->table}` WHERE username = ? LIMIT 1", [$username]);

        return $id === null ? null : (int) $id;
    }

    public function get_id_by_email($email) {
        $id = $this->value("SELECT id FROM `{$this->table}` WHERE email = ? LIMIT 1", [$email]);

        return $id === null ? null : (int) $id;
    }

    public function get_role($id) {
        return $this->value("SELECT `role` FROM `{$this->table}` WHERE id = ? LIMIT 1", [$id]);
    }
}
