<?php
namespace App\Controllers;

use App\Models\User;
use App\Repositories\UserRepository;

class UserController extends CrudController
{
    protected $repository_class = UserRepository::class;
    protected $model_class = User::class;
    protected $view_path = 'users';
    protected $entity_name = 'user';
    protected $write_roles = ['admin'];
    protected $index_filter_keys = ['q', 'role', 'status'];
    private const MANAGEABLE_ROLES = ['admin', 'schedule_manager', 'teacher', 'student'];

    public function profile()
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $user = $this->repository->find_by_id($this->request_user_id());
        if ($user === null) {
            return $this->not_found('User not found.');
        }

        return $this->respond('users/profile', [
            'user' => $user,
            'events' => $this->repository->get_requested_events($user->id),
        ]);
    }

    public function events($id = null)
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $id = $id !== null && is_numeric($id) ? (int) $id : $this->request_user_id();

        return $this->success(['events' => $this->repository->get_requested_events($id)]);
    }

    public function update_role_status($id = null)
    {
        if ($guard = $this->guarded($this->write_roles)) {
            return $guard;
        }

        $id = $this->resolve_id($id);
        if ($id === $this->request_user_id()) {
            return $this->error('You cannot change your own role or status from this screen.', 422);
        }

        $user = $this->repository->find_by_id($id);
        if ($user === null) {
            return $this->not_found('User not found.');
        }

        $data = $this->payload();
        $role = strtolower(trim((string) ($data['role'] ?? '')));
        if (!in_array($role, self::MANAGEABLE_ROLES, true)) {
            return $this->error('Selected role is invalid.', 422);
        }

        $status = (string) ($data['is_active'] ?? '0');
        $is_active = in_array($status, ['1', 'true', 'on'], true);

        $this->repository->update_role_status($id, $role, $is_active);

        return $this->success([
            'user' => $this->repository->find_by_id($id),
        ]);
    }

    protected function prepare_payload($data)
    {
        if (isset($data['password']) && trim((string) $data['password']) !== '') {
            $data['password_hash'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        }

        unset($data['password']);

        return $data;
    }
}
