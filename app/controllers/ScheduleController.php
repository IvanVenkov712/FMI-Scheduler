<?php

declare(strict_types=1);

class ScheduleController extends CrudController
{
    protected string $table = 'schedules';
    protected string $viewPath = 'schedules';
    protected string $entityName = 'schedule';
    protected array $fillable = ['name', 'type', 'academic_year', 'semester', 'starts_on', 'ends_on', 'status', 'created_by'];
    protected array $required = ['name', 'starts_on', 'ends_on'];
    protected array $integerFields = ['created_by'];
    protected array $listFilters = ['type', 'status', 'semester'];
    protected string $defaultOrder = 'starts_on DESC';

    public function store(): mixed
    {
        $_POST['created_by'] ??= $this->currentUserId();
        return parent::store();
    }

    public function calendar(int|string|null $id = null): mixed
    {
        $params = [];
        $where = '';

        if ($id !== null) {
            $where = 'WHERE schedule_events.schedule_id = :schedule_id';
            $params['schedule_id'] = $id;
        }

        return $this->respond('schedules/calendar', [
            'schedule' => $id === null ? null : $this->findOrFail($id),
            'events' => $this->database()->fetchAll(
                'SELECT events.*, schedule_events.schedule_id, schedule_events.assigned_room_id,
                        schedule_events.allocation_status, rooms.code AS room_code
                 FROM schedule_events
                 INNER JOIN events ON events.id = schedule_events.event_id
                 LEFT JOIN rooms ON rooms.id = COALESCE(schedule_events.assigned_room_id, events.room_id)
                 ' . $where . '
                 ORDER BY events.starts_at ASC',
                $params
            ),
        ]);
    }

    public function attachEvent(int|string $id): mixed
    {
        $data = $this->payload();
        $errors = $this->requireFields($data, ['event_id']);

        if ($errors !== []) {
            return $this->validationResponse($errors, $data, 'schedules/details');
        }

        $this->database()->insert(
            'INSERT INTO schedule_events (schedule_id, event_id, assigned_room_id, allocation_status)
             VALUES (:schedule_id, :event_id, :assigned_room_id, :allocation_status)
             ON DUPLICATE KEY UPDATE assigned_room_id = VALUES(assigned_room_id), allocation_status = VALUES(allocation_status)',
            [
                'schedule_id' => $id,
                'event_id' => $data['event_id'],
                'assigned_room_id' => $data['assigned_room_id'] ?? null,
                'allocation_status' => $data['allocation_status'] ?? 'manual',
            ]
        );

        return $this->updatedResponse($this->findOrFail($id));
    }

    protected function domainErrors(array $data): array
    {
        if (!empty($data['starts_on']) && !empty($data['ends_on']) && strtotime((string) $data['ends_on']) < strtotime((string) $data['starts_on'])) {
            return ['ends_on' => 'End date must be on or after start date.'];
        }

        return [];
    }
}
