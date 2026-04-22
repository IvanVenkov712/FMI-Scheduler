<?php

declare(strict_types=1);

class MovementConstraintRepository
{
    public function __construct(
        private readonly ?Database $database = null
    ) {
    }

    public function active(?DateTimeInterface $at = null): array
    {
        $at ??= new DateTimeImmutable();

        $rows = $this->db()->fetchAll(
            'SELECT * FROM movement_constraints
             WHERE is_active = 1
               AND (starts_at IS NULL OR starts_at <= :at)
               AND (ends_at IS NULL OR ends_at >= :at)
             ORDER BY type ASC, scope ASC, id ASC',
            ['at' => $at->format('Y-m-d H:i:s')]
        );

        return array_map(static fn (array $row): MovementConstraint => new MovementConstraint($row), $rows);
    }

    public function find(int|string $id): ?MovementConstraint
    {
        return MovementConstraint::find($id);
    }

    public function create(array $data): MovementConstraint
    {
        $payload = $data;
        $payload['rule_data'] = json_encode($data['rule_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $id = $this->db()->insert(
            'INSERT INTO movement_constraints
             (name, type, scope, building_id, floor_id, room_id, route_id, rule_data, description, starts_at, ends_at, is_active, created_by)
             VALUES
             (:name, :type, :scope, :building_id, :floor_id, :room_id, :route_id, :rule_data, :description, :starts_at, :ends_at, :is_active, :created_by)',
            [
                'name' => $payload['name'],
                'type' => $payload['type'],
                'scope' => $payload['scope'],
                'building_id' => $payload['building_id'] ?? null,
                'floor_id' => $payload['floor_id'] ?? null,
                'room_id' => $payload['room_id'] ?? null,
                'route_id' => $payload['route_id'] ?? null,
                'rule_data' => $payload['rule_data'],
                'description' => $payload['description'] ?? null,
                'starts_at' => $payload['starts_at'] ?? null,
                'ends_at' => $payload['ends_at'] ?? null,
                'is_active' => $payload['is_active'],
                'created_by' => $payload['created_by'] ?? null,
            ]
        );

        return $this->find((int) $id) ?? new MovementConstraint();
    }

    public function update(int|string $id, array $data): ?MovementConstraint
    {
        $constraint = $this->find($id);
        if ($constraint === null) {
            return null;
        }

        $constraint->fill([
            'name' => $data['name'],
            'type' => $data['type'],
            'scope' => $data['scope'],
            'building_id' => $data['building_id'] ?? null,
            'floor_id' => $data['floor_id'] ?? null,
            'room_id' => $data['room_id'] ?? null,
            'route_id' => $data['route_id'] ?? null,
            'rule_data' => json_encode($data['rule_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'description' => $data['description'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'created_by' => $data['created_by'] ?? null,
        ]);
        $constraint->save();

        return $constraint;
    }

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }
}
