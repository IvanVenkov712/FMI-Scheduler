<?php
namespace App\Controllers;

use App\Models\User;
use App\Repositories\UserRepository;

class UserController extends CrudController
{
    public function __construct($request = [], $view = null, $session = null, $repository = null)
    {
        parent::__construct($request, $view, $session);
        $this->repository = $repository ?? new UserRepository();
        $this->model_class = User::class;
        $this->view_path = 'users';
        $this->entity_name = 'user';
        $this->write_roles = ['admin'];
    }

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

    protected function new_model($data)
    {
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }

        return new User($data);
    }

    public function update($id = null)
    {
        if ($guard = $this->guarded($this->write_roles)) {
            return $guard;
        }

        $id = $this->resolve_id($id);
        $existing = $this->repository->find_by_id($id);
        if ($existing === null) {
            return $this->not_found('User not found.');
        }

        $data = $this->payload();
        if (isset($data['password']) && trim((string) $data['password']) !== '') {
            $data['password_hash'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        }

        unset($data['password']);
        $existing->fill($data);
        $this->repository->update($id, $existing);

        return $this->success(['user' => $this->repository->find_by_id($id)]);
    }
}
