<?php

declare(strict_types=1);

class StudentGroupRepository
{
    public function __construct(
        private readonly ?Database $database = null
    ) {
    }

    public function find(int|string $id): ?StudentGroup
    {
        return (new GenericModelRepository($this->database))->find(StudentGroup::class, $id);
    }

    public function findByCode(string $code): ?StudentGroup
    {
        $row = $this->db()->fetch('SELECT * FROM student_groups WHERE code = :code LIMIT 1', ['code' => $code]);

        return $row === null ? null : new StudentGroup($row);
    }

    public function all(): array
    {
        return array_map(
            static fn (array $row): StudentGroup => new StudentGroup($row),
            $this->db()->fetchAll('SELECT * FROM student_groups ORDER BY name ASC, id ASC')
        );
    }

    public function exportRows(): array
    {
        return $this->db()->fetchAll(
            'SELECT student_groups.*,
                    courses.code AS course_code
             FROM student_groups
             LEFT JOIN courses ON courses.id = student_groups.course_id
             ORDER BY student_groups.name ASC, student_groups.id ASC'
        );
    }

    public function create(array $data): StudentGroup
    {
        $group = new StudentGroup($data);
        (new GenericModelRepository($this->database))->save($group);

        return $group;
    }

    public function update(int|string $id, array $data): ?StudentGroup
    {
        $group = $this->find($id);
        if ($group === null) {
            return null;
        }

        $group->fill($data);
        (new GenericModelRepository($this->database))->save($group);

        return $group;
    }

    public function syncMembers(int $groupId, array $userIds): void
    {
        $this->db()->transaction(function (Database $db) use ($groupId, $userIds): void {
            $db->execute('DELETE FROM student_group_members WHERE student_group_id = :group_id', ['group_id' => $groupId]);

            foreach (array_values(array_unique(array_filter(array_map('intval', $userIds)))) as $userId) {
                $db->insert(
                    'INSERT INTO student_group_members (student_group_id, user_id) VALUES (:group_id, :user_id)',
                    ['group_id' => $groupId, 'user_id' => $userId]
                );
            }
        });
    }

    public function groupsForStudent(int $userId): array
    {
        return $this->db()->fetchAll(
            'SELECT student_groups.*
             FROM student_group_members
             INNER JOIN student_groups ON student_groups.id = student_group_members.student_group_id
             WHERE student_group_members.user_id = :user_id
             ORDER BY student_groups.name ASC, student_groups.id ASC',
            ['user_id' => $userId]
        );
    }

    public function memberIds(int $groupId): array
    {
        $rows = $this->db()->fetchAll(
            'SELECT user_id FROM student_group_members WHERE student_group_id = :group_id',
            ['group_id' => $groupId]
        );

        return array_map(static fn (array $row): int => (int) $row['user_id'], $rows);
    }

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }
}
