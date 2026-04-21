<?php

declare(strict_types=1);

class AllocationParameterRepository
{
    public function __construct(
        private readonly ?Database $database = null
    ) {
    }

    public function find(int|string $id): ?AllocationParameter
    {
        return AllocationParameter::find($id);
    }

    public function findByCode(string $code): ?AllocationParameter
    {
        $row = $this->db()->fetch('SELECT * FROM allocation_parameters WHERE code = :code LIMIT 1', ['code' => $code]);

        return $row === null ? null : new AllocationParameter($row);
    }

    public function all(): array
    {
        return array_map(
            static fn (array $row): AllocationParameter => new AllocationParameter($row),
            $this->db()->fetchAll('SELECT * FROM allocation_parameters ORDER BY name ASC, id ASC')
        );
    }

    public function exportRows(): array
    {
        return $this->db()->fetchAll(
            'SELECT allocation_parameters.*,
                    courses.code AS course_code,
                    student_groups.code AS student_group_code
             FROM allocation_parameters
             LEFT JOIN courses ON courses.id = allocation_parameters.course_id
             LEFT JOIN student_groups ON student_groups.id = allocation_parameters.student_group_id
             ORDER BY allocation_parameters.name ASC, allocation_parameters.id ASC'
        );
    }

    public function create(array $data): AllocationParameter
    {
        $item = new AllocationParameter($data);
        $item->save();

        return $item;
    }

    public function update(int|string $id, array $data): ?AllocationParameter
    {
        $item = $this->find($id);
        if ($item === null) {
            return null;
        }

        $item->fill($data);
        $item->save();

        return $item;
    }

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }
}
