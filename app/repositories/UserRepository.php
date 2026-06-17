<?php
namespace App\Repositories;
use App\Core\Repository;
use App\Models\Event;
use App\Models\User;

class UserRepository extends Repository {
    protected $model_class = User::class;
    protected $table = 'users';

    public function find_filtered($filters = []) {
        $conditions = [];
        $params = [];

        $this->add_any_like_condition($conditions, $params, [
            '`username`',
            '`email`',
            '`first_name`',
            '`last_name`',
            '`role`',
        ], $filters['q'] ?? '');

        $role = trim((string) ($filters['role'] ?? ''));
        if (in_array($role, ['admin', 'schedule_manager', 'teacher', 'student'], true)) {
            $conditions[] = '`role` = ?';
            $params[] = $role;
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if (in_array($status, ['active', 'inactive'], true)) {
            $conditions[] = '`is_active` = ?';
            $params[] = $status === 'active' ? 1 : 0;
        }

        return $this->rows(
            "SELECT * FROM `{$this->table}`" . $this->where_clause($conditions) . " ORDER BY `username` ASC",
            $params
        );
    }

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

    public function update_role_status($id, $role, $is_active)
    {
        $statement = $this->run(
            "UPDATE `{$this->table}` SET `role` = ?, `is_active` = ? WHERE id = ?",
            [$role, $is_active ? 1 : 0, $id]
        );

        return $statement->rowCount() > 0;
    }

    public function update_profile_credentials($id, $email, $password_hash = null)
    {
        $assignments = ['`email` = ?'];
        $params = [$email];

        if ($password_hash !== null) {
            $assignments[] = '`password_hash` = ?';
            $params[] = $password_hash;
        }

        $params[] = $id;
        $statement = $this->run(
            "UPDATE `{$this->table}` SET " . implode(', ', $assignments) . " WHERE id = ?",
            $params
        );

        return $statement->rowCount() > 0;
    }
}
