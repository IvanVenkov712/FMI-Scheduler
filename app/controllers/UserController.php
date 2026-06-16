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

    protected function prepare_payload($data)
    {
        if (isset($data['password']) && trim((string) $data['password']) !== '') {
            $data['password_hash'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        }

        unset($data['password']);

        return $data;
    }
}
