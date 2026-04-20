<?php

declare(strict_types=1);

class UserController extends CrudController
{
    protected string $table = 'users';
    protected string $viewPath = 'users';
    protected string $entityName = 'user';
    protected array $fillable = [
        'role_id', 'username', 'email', 'password_hash', 'first_name', 'last_name',
        'phone', 'faculty_number', 'department', 'status',
    ];
    protected array $required = ['username', 'email', 'first_name', 'last_name'];
    protected array $integerFields = ['role_id'];
    protected array $listFilters = ['role_id', 'status', 'department'];
    protected string $defaultOrder = 'last_name ASC, first_name ASC';

    public function profile(): string
    {
        $id = $_SESSION['user']['id'] ?? null;

        if ($id === null) {
            return $this->render('errors/403', ['message' => 'Login is required.']);
        }

        return $this->render('users/profile', ['user' => $this->publicUser($this->findOrFail($id))]);
    }

    public function manage(): mixed
    {
        return $this->index();
    }

    public function store(): mixed
    {
        $data = $this->body() + $this->input();

        if (!empty($data['password'])) {
            $_POST['password_hash'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        }

        return parent::store();
    }

    public function update(int|string $id): mixed
    {
        $data = $this->body() + $this->input();

        if (!empty($data['password'])) {
            $_POST['password_hash'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        }

        return parent::update($id);
    }

    public function details(int|string $id): mixed
    {
        return $this->respond('users/profile', ['user' => $this->publicUser($this->findOrFail($id))]);
    }

    protected function rows(?string $sql = null, array $params = []): array
    {
        $rows = parent::rows(
            $sql ?? 'SELECT users.id, users.role_id, users.username, users.email, users.first_name,
                            users.last_name, users.phone, users.faculty_number, users.department,
                            users.status, users.last_login_at, users.created_at, roles.name AS role_name
                     FROM users
                     LEFT JOIN roles ON roles.id = users.role_id
                     ORDER BY users.last_name ASC, users.first_name ASC',
            $params
        );

        return array_map(fn (array $user): array => $this->publicUser($user), $rows);
    }

    protected function formData(): array
    {
        return [
            'roles' => $this->database()->fetchAll('SELECT id, name, slug FROM roles ORDER BY name ASC'),
        ];
    }

    private function publicUser(array $user): array
    {
        unset($user['password_hash'], $user['remember_token']);

        return $user;
    }
}
