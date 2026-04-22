<?php

declare(strict_types=1);

class UserService
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly AuditLogRepository $auditLogs = new AuditLogRepository(),
        private readonly ValidationService $validation = new ValidationService()
    ) {
    }

    public function find(int|string $id): ?User
    {
        return $this->users->find($id);
    }

    public function create(array $data, ?int $userId = null): array
    {
        $payload = $this->normalize($data);
        $result = $this->validate($payload, true);

        if (!$result['valid']) {
            return ['success' => false, 'errors' => $result['errors']];
        }

        if ($this->users->findByLogin((string) $payload['username']) !== null) {
            return ['success' => false, 'errors' => ['username' => ['Username is already taken.']]];
        }

        if ($this->users->findByEmail((string) $payload['email']) !== null) {
            return ['success' => false, 'errors' => ['email' => ['Email is already taken.']]];
        }

        $user = $this->users->create($payload);
        $this->auditLogs->record('create', 'user', $user->get('id'), null, $user->publicData(), $userId);

        return ['success' => true, 'user' => $user];
    }

    public function update(int|string $id, array $data, ?int $userId = null): array
    {
        $user = $this->users->find($id);
        if ($user === null) {
            return ['success' => false, 'errors' => ['user' => ['User not found.']]];
        }

        $payload = $this->normalize($data);
        $merged = $payload + $user->toArray();
        $result = $this->validate($merged, false);

        if (!$result['valid']) {
            return ['success' => false, 'errors' => $result['errors']];
        }

        if (isset($payload['username'])) {
            $existing = $this->users->findByLogin((string) $payload['username']);
            if ($existing !== null && (int) $existing->get('id') !== (int) $user->get('id')) {
                return ['success' => false, 'errors' => ['username' => ['Username is already taken.']]];
            }
        }

        if (isset($payload['email'])) {
            $existing = $this->users->findByEmail((string) $payload['email']);
            if ($existing !== null && (int) $existing->get('id') !== (int) $user->get('id')) {
                return ['success' => false, 'errors' => ['email' => ['Email is already taken.']]];
            }
        }

        $old = $user->publicData();
        $updated = $this->users->update($id, $payload);
        $this->auditLogs->record('update', 'user', $id, $old, $updated?->publicData(), $userId);

        return ['success' => true, 'user' => $updated];
    }

    public function delete(int|string $id, ?int $userId = null): bool
    {
        $user = $this->users->find($id);
        if ($user === null) {
            return false;
        }

        $deleted = $this->users->delete($id);
        if ($deleted) {
            $this->auditLogs->record('delete', 'user', $id, $user->publicData(), null, $userId);
        }

        return $deleted;
    }

    public function resetPassword(int|string $id, ?int $userId = null): array
    {
        $user = $this->users->find($id);
        if ($user === null) {
            return ['success' => false, 'errors' => ['user' => ['User not found.']]];
        }

        $temporaryPassword = $this->generateTemporaryPassword();
        $updated = $this->users->update($id, [
            'password_hash' => password_hash($temporaryPassword, PASSWORD_DEFAULT),
            'remember_token' => null,
        ]);

        if ($updated === null) {
            return ['success' => false, 'errors' => ['user' => ['User could not be updated.']]];
        }

        $this->auditLogs->record(
            'reset_password',
            'user',
            $id,
            ['password_reset' => false],
            ['password_reset' => true],
            $userId
        );

        return [
            'success' => true,
            'user' => $updated,
            'temporary_password' => $temporaryPassword,
        ];
    }

    private function normalize(array $data): array
    {
        $data = $this->validation->normalizeIntegers($data, ['role_id']);

        if (!empty($data['password'])) {
            $data['password_hash'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        }

        unset($data['password'], $data['password_confirmation']);

        return $data;
    }

    private function validate(array $data, bool $requirePasswordHash): array
    {
        $rules = [
            'role_id' => 'required|integer|min:1',
            'username' => 'required|string|min:3|max:80',
            'email' => 'required|email|max:190',
            'password_hash' => 'nullable|string|max:255',
            'first_name' => 'required|string|max:120',
            'last_name' => 'required|string|max:120',
            'phone' => 'nullable|string|max:60',
            'faculty_number' => 'nullable|string|max:60',
            'department' => 'nullable|string|max:120',
            'status' => 'nullable|in:active,inactive',
        ];

        if ($requirePasswordHash) {
            $rules['password_hash'] = 'required|string|max:255';
        }

        return $this->validation->validate($data, $rules);
    }

    private function generateTemporaryPassword(int $length = 12): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
        $maxIndex = strlen($alphabet) - 1;
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $maxIndex)];
        }

        return $password;
    }
}
