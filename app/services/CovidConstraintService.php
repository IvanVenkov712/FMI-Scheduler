<?php

declare(strict_types=1);

class CovidConstraintService
{
    public function __construct(
        private readonly MovementConstraintRepository $constraints = new MovementConstraintRepository(),
        private readonly AuditLogRepository $auditLogs = new AuditLogRepository(),
        private readonly ValidationService $validation = new ValidationService()
    ) {
    }

    public function active(?DateTimeInterface $at = null): array
    {
        return $this->constraints->active($at);
    }

    public function find(int|string $id): ?MovementConstraint
    {
        return $this->constraints->find($id);
    }

    public function create(array $data, ?int $userId = null): array
    {
        $data = $this->normalizeConstraintData($data, $userId);
        $errors = $this->validateConstraintData($data);

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $constraint = $this->constraints->create($data);
        $this->auditLogs->record('create', 'movement_constraint', $constraint->get('id'), null, $constraint->toArray(), $userId);

        return ['success' => true, 'constraint' => $constraint];
    }

    public function update(int|string $id, array $data, ?int $userId = null): array
    {
        $constraint = $this->find($id);
        if ($constraint === null) {
            return ['success' => false, 'errors' => ['constraint' => ['Constraint not found.']]];
        }

        $payload = $this->normalizeConstraintData($data, $userId) + $constraint->toArray();
        $errors = $this->validateConstraintData($payload);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $old = $constraint->toArray();
        $constraint = $this->constraints->update($id, $payload);
        $this->auditLogs->record('update', 'movement_constraint', $id, $old, $constraint?->toArray(), $userId);

        return ['success' => true, 'constraint' => $constraint];
    }

    public function effectiveRoomCapacity(Room $room): int
    {
        $capacity = $room->effectiveCapacity();

        foreach ($this->active() as $constraint) {
            if (!$this->matchesRoom($constraint, $room)) {
                continue;
            }

            $ruleData = $constraint->ruleData();
            if (isset($ruleData['capacity_limit'])) {
                $capacity = min($capacity, (int) $ruleData['capacity_limit']);
            }

            if (isset($ruleData['capacity_percent'])) {
                $capacity = min($capacity, (int) floor(((int) $room->get('capacity', $capacity)) * ((float) $ruleData['capacity_percent'] / 100)));
            }
        }

        return max(0, $capacity);
    }

    public function routeAllowed(Route $route): bool
    {
        foreach ($this->active() as $constraint) {
            if (!$this->matchesRoute($constraint, $route)) {
                continue;
            }

            $ruleData = $constraint->ruleData();
            if (!empty($ruleData['blocked']) || in_array($constraint->get('type'), ['route', 'accessibility'], true) && !$route->isAccessible()) {
                return false;
            }
        }

        return $route->isActive();
    }

    public function recommendedBufferMinutes(?Room $room = null): int
    {
        $buffer = 0;

        foreach ($this->active() as $constraint) {
            if ($room !== null && !$this->matchesRoom($constraint, $room) && $constraint->get('scope') !== 'global') {
                continue;
            }

            $ruleData = $constraint->ruleData();
            if (isset($ruleData['buffer_minutes'])) {
                $buffer = max($buffer, (int) $ruleData['buffer_minutes']);
            }
        }

        return $buffer;
    }

    private function matchesRoom(MovementConstraint $constraint, Room $room): bool
    {
        return match ((string) $constraint->get('scope', 'global')) {
            'global' => true,
            'building' => (int) $constraint->get('building_id') === (int) $room->floor()?->get('building_id'),
            'floor' => (int) $constraint->get('floor_id') === (int) $room->get('floor_id'),
            'room' => (int) $constraint->get('room_id') === (int) $room->get('id'),
            default => false,
        };
    }

    private function matchesRoute(MovementConstraint $constraint, Route $route): bool
    {
        return match ((string) $constraint->get('scope', 'global')) {
            'global' => true,
            'route' => (int) $constraint->get('route_id') === (int) $route->get('id'),
            'building' => (int) $constraint->get('building_id') === (int) $route->get('from_building_id')
                || (int) $constraint->get('building_id') === (int) $route->get('to_building_id'),
            default => false,
        };
    }

    private function normalizeConstraintData(array $data, ?int $userId): array
    {
        $data = $this->validation->normalizeIntegers($data, ['building_id', 'floor_id', 'room_id', 'route_id', 'created_by']);
        $data = $this->validation->normalizeBooleans($data, ['is_active']);
        $data['created_by'] = $data['created_by'] ?? $userId;
        $data['is_active'] = $data['is_active'] ?? 1;

        return $data;
    }

    private function validateConstraintData(array $data): array
    {
        $result = $this->validation->validate($data, [
            'name' => 'required|string|max:160',
            'type' => 'required|in:covid,capacity,route,time_buffer,accessibility,other',
            'scope' => 'required|in:global,building,floor,room,route',
            'rule_data' => 'required|array',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $errors = $result['errors'];
        if (!empty($data['starts_at']) && !empty($data['ends_at'])) {
            $errors = array_merge_recursive(
                $errors,
                $this->validation->validateDateRange((string) $data['starts_at'], (string) $data['ends_at'])
            );
        }

        return $errors;
    }

}
