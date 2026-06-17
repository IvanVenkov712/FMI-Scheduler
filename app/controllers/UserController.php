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

    public function update_profile()
    {
        if ($guard = $this->guarded()) {
            return $guard;
        }

        $user = $this->repository->find_by_id($this->request_user_id());
        if ($user === null) {
            return $this->not_found('User not found.');
        }

        $data = $this->payload();
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $current_password = (string) ($data['current_password'] ?? '');
        $new_password = (string) ($data['new_password'] ?? '');
        $new_password_confirmation = (string) ($data['new_password_confirmation'] ?? '');
        $password_change_requested = $new_password !== '' || $new_password_confirmation !== '';

        $errors = [];
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Enter a valid email address.';
        }

        $existing_email_user = $email === '' ? null : $this->repository->find_by_email($email);
        if ($existing_email_user !== null && (int) $existing_email_user->id !== (int) $user->id) {
            $errors['email'] = 'This email is already used by another account.';
        }

        if ($current_password === '' || !password_verify($current_password, $user->password_hash)) {
            $errors['current_password'] = 'Enter your current password.';
        }

        if ($password_change_requested) {
            if (strlen($new_password) < 8) {
                $errors['new_password'] = 'New password must be at least 8 characters.';
            }

            if ($new_password !== $new_password_confirmation) {
                $errors['new_password_confirmation'] = 'Password confirmation does not match.';
            }
        }

        if (!empty($errors)) {
            return $this->error('Please correct the highlighted information.', 422, $errors);
        }

        $password_hash = $password_change_requested
            ? password_hash($new_password, PASSWORD_DEFAULT)
            : null;

        $this->repository->update_profile_credentials($user->id, $email, $password_hash);
        $updated_user = $this->repository->find_by_id($user->id);
        if ($updated_user !== null) {
            $this->session->login($updated_user);
        }

        return $this->success(['user' => $updated_user]);
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
