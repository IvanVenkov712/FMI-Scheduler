<?php
namespace App\Services;

use Throwable;

abstract class BaseService
{
    protected function dependency($value, $class, ...$args)
    {
        return $value ?? new $class(...$args);
    }

    protected function transaction($repository, $callback)
    {
        $repository->begin_transaction();

        try {
            $result = $callback();
            $repository->commit();

            return $result;
        } catch (ServiceAbort $abort) {
            $repository->roll_back();

            return $abort->result();
        } catch (Throwable $exception) {
            $repository->roll_back();
            throw $exception;
        }
    }

    protected function abort($result = null)
    {
        throw new ServiceAbort($result);
    }

    protected function refresh($repository, $id)
    {
        return $repository->find_by_id((int) $id);
    }

    protected function update_and_refresh($repository, $id, $model)
    {
        $repository->update($id, $model);

        return $this->refresh($repository, $id);
    }

    protected function normalize_role($role)
    {
        if ($role === null) {
            return null;
        }

        $role = strtolower(trim((string) $role));

        return $role === '' ? null : $role;
    }

    protected function has_role_value($role, $allowed_roles)
    {
        return $role !== null && in_array($this->normalize_role($role), $allowed_roles, true);
    }

    protected function is_active_user($user)
    {
        return isset($user->is_active) && (bool) $user->is_active;
    }

    protected function normalize_email($value)
    {
        $email = strtolower(trim((string) $value));

        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    protected function normalize_newlines($value)
    {
        return str_replace(["\r\n", "\r"], "\n", (string) $value);
    }
}

class ServiceAbort extends \RuntimeException
{
    private $result;

    public function __construct($result = null)
    {
        parent::__construct();
        $this->result = $result;
    }

    public function result()
    {
        return $this->result;
    }
}
