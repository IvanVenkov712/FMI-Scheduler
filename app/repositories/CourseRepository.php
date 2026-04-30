<?php

declare(strict_types=1);

class CourseRepository extends GenericModelRepository
{

    public function find(int|string $id): ?Course
    {
        return $this->findModel(Course::class, $id);
    }

    public function findByCode(string $code): ?Course
    {
        $row = $this->db()->fetch('SELECT * FROM courses WHERE code = :code LIMIT 1', ['code' => $code]);

        return $row === null ? null : new Course($row);
    }

    public function all(): array
    {
        return array_map(static fn (array $row): Course => new Course($row), $this->db()->fetchAll('SELECT * FROM courses ORDER BY name ASC, id ASC'));
    }

    public function create(array $data): Course
    {
        $course = new Course($data);
        $this->saveModel($course);

        return $course;
    }

    public function update(int|string $id, array $data): ?Course
    {
        $course = $this->find($id);
        if ($course === null) {
            return null;
        }

        $course->fill($data);
        $this->saveModel($course);

        return $course;
    }
}


