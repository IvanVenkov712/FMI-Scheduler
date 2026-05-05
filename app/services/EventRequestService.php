<?php
namespace App\Services;

use App\Core\Database;
use App\models\Event;
use App\models\OnlineAccess;
use App\Repositories\OnlineAccessRepository;
use App\Repositories\EventRepository;
use App\Repositories\ScheduleRepository;

class EventRequestService
{
    private $event_repository;
    private $schedule_repository;
    private $online_access_repository;
    private $room_availability_service;

    public function __construct(
        $event_repository = null,
        $schedule_repository = null,
        $online_access_repository = null,
        $room_availability_service = null,
    ) {
        $this->event_repository = $event_repository ?? new EventRepository();
        $this->schedule_repository = $schedule_repository ?? new ScheduleRepository();
        $this->online_access_repository = $online_access_repository ?? new OnlineAccessRepository();
        $this->room_availability_service = $room_availability_service ?? new RoomAvailabilityService(null, $this->event_repository);
    }

    public function submit_event_request($event, $requirements = null, $online_access=null)
    {
        if ($event === null) {
            return null;
        }

        $event_id = (int) $this->event_repository->insert($event);
        $this->replace_resource_requirements($event_id, $requirements);

        if ($online_access !== null) {
            $this->upsert_online_access($event_id, $online_access);
        }

        return $this->event_repository->find_by_id($event_id);
    }

    public function edit_pending_request($event_id, $data)
    {
        if (!is_array($data)) {
            return null;
        }

        $existing_event = $this->event_repository->find_by_id($event_id);
        if ($existing_event === null || !$this->is_pending($existing_event)) {
            return null;
        }

        $event = $this->build_event_from_data($data, $existing_event);
        if ($event === null) {
            return null;
        }

        $requirements = array_key_exists('resource_requirements', $data)
            ? $this->normalize_resource_requirements($data['resource_requirements'])
            : $this->event_repository->get_resource_requirements($event_id);

        if ($requirements === null || !$this->is_request_bookable($event, $requirements)) {
            return null;
        }

        $online_access = array_key_exists('online_access', $data)
            ? $this->normalize_online_access($data['online_access'])
            : $this->event_repository->get_online_access($event_id);

        if (!$this->is_online_access_valid_for_event($event, $online_access)) {
            return null;
        }

        $connection = $this->database->connection();
        $connection->beginTransaction();

        try {
            $this->event_repository->update($event_id, $event);

            if (array_key_exists('resource_requirements', $data)) {
                $this->replace_resource_requirements($event_id, $requirements);
            }

            if (array_key_exists('online_access', $data)) {
                if ($online_access === null) {
                    $this->delete_online_access($event_id);
                } else {
                    $this->upsert_online_access($event_id, $online_access);
                }
            }

            $connection->commit();

            return $this->event_repository->find_by_id($event_id);
        } catch (\Throwable $exception) {
            $connection->rollBack();
            return null;
        }
    }

    public function attach_resource_requirements($event_id, $resource_requirements)
    {
        $event = $this->event_repository->find_by_id($event_id);
        if ($event === null || !$this->is_pending($event)) {
            return false;
        }

        $requirements = $this->normalize_resource_requirements($resource_requirements);
        if ($requirements === null || !$this->is_request_bookable($event, $requirements)) {
            return false;
        }

        $connection = $this->database->connection();
        $connection->beginTransaction();

        try {
            $this->replace_resource_requirements($event_id, $requirements);
            $connection->commit();
            return true;
        } catch (\Throwable $exception) {
            $connection->rollBack();
            return false;
        }
    }

    public function attach_online_access($event_id, $online_access_data)
    {
        $event = $this->event_repository->find_by_id($event_id);
        if ($event === null || !$this->is_pending($event)) {
            return null;
        }

        $online_access = $this->normalize_online_access($online_access_data);
        if ($online_access === null || !$this->is_online_access_valid_for_event($event, $online_access)) {
            return null;
        }

        $connection = $this->database->connection();
        $connection->beginTransaction();

        try {
            $this->upsert_online_access($event_id, $online_access);
            $connection->commit();
            return $this->event_repository->get_online_access($event_id);
        } catch (\Throwable $exception) {
            $connection->rollBack();
            return null;
        }
    }

    public function submitEventRequest($data)
    {
        return $this->submit_event_request($data);
    }

    public function editPendingRequest($event_id, $data)
    {
        return $this->edit_pending_request($event_id, $data);
    }

    public function attachResourceRequirements($event_id, $resource_requirements)
    {
        return $this->attach_resource_requirements($event_id, $resource_requirements);
    }

    public function attachOnlineAccess($event_id, $online_access_data)
    {
        return $this->attach_online_access($event_id, $online_access_data);
    }

    private function build_event_from_data($data, $existing_event = null)
    {
        $event = $existing_event === null ? new Event() : clone $existing_event;

        $event->requestor_id = (int) ($data['requestor_id'] ?? $event->requestor_id);
        $event->room_id = (int) ($data['room_id'] ?? $event->room_id);
        $event->name = $this->normalize_string($data['name'] ?? $event->name);
        $event->type = $this->normalize_string($data['type'] ?? $event->type);
        $event->start_time = $this->normalize_string($data['start_time'] ?? $event->start_time);
        $event->end_time = $this->normalize_string($data['end_time'] ?? $event->end_time);
        $event->delivery_mode = $this->normalize_delivery_mode($data['delivery_mode'] ?? $event->delivery_mode);
        $event->subject_name = $this->normalize_string($data['subject_name'] ?? $event->subject_name);
        $event->student_count = (int) ($data['student_count'] ?? $event->student_count);
        $event->status = 'pending';

        if ($event->requestor_id <= 0
            || $event->room_id <= 0
            || $event->name === ''
            || $event->type === ''
            || $event->student_count < 0
            || !$this->is_valid_interval($event->start_time, $event->end_time)
        ) {
            return null;
        }

        return $event;
    }

    private function normalize_resource_requirements($resource_requirements)
    {
        if ($resource_requirements === null) {
            return [];
        }

        if (!is_array($resource_requirements)) {
            return null;
        }

        $normalized = [];
        foreach ($resource_requirements as $requirement) {
            if (is_array($requirement)) {
                $resource_id = $requirement['resource_id'] ?? null;
                $required_quantity = $requirement['required_quantity'] ?? $requirement['quantity'] ?? 1;
                $notes = $requirement['notes'] ?? '';
            } elseif (is_object($requirement)) {
                $resource_id = $requirement->resource_id ?? null;
                $required_quantity = $requirement->required_quantity ?? $requirement->quantity ?? 1;
                $notes = $requirement->notes ?? '';
            } else {
                return null;
            }

            $resource_id = (int) $resource_id;
            $required_quantity = (int) $required_quantity;

            if ($resource_id <= 0 || $required_quantity < 0) {
                return null;
            }

            if ($required_quantity === 0) {
                continue;
            }

            $normalized[$resource_id] = (object) [
                'resource_id' => $resource_id,
                'required_quantity' => $required_quantity,
                'notes' => $this->normalize_string($notes),
            ];
        }

        return array_values($normalized);
    }

    private function normalize_online_access($online_access_data)
    {
        if ($online_access_data === null || $online_access_data === []) {
            return null;
        }

        $data = is_object($online_access_data) ? get_object_vars($online_access_data) : $online_access_data;
        if (!is_array($data)) {
            return null;
        }

        $online_access = new OnlineAccess();
        $online_access->platform = $this->normalize_string($data['platform'] ?? '');
        $online_access->meeting_link = $this->normalize_string($data['meeting_link'] ?? $data['link'] ?? '');
        $online_access->access_key = $this->normalize_string($data['access_key'] ?? $data['key'] ?? '');
        $online_access->host_notes = $this->normalize_string($data['host_notes'] ?? $data['notes'] ?? '');

        if ($online_access->platform === '' || $online_access->meeting_link === '') {
            return null;
        }

        return $online_access;
    }

    private function is_request_bookable($event, $resource_requirements)
    {
        if ($event->delivery_mode === 'online') {
            return true;
        }

        return $this->room_availability_service->is_room_bookable(
            $event->room_id,
            $event->start_time,
            $event->end_time,
            $event->student_count,
            $resource_requirements
        );
    }

    private function is_online_access_valid_for_event($event, $online_access)
    {
        if ($event->delivery_mode === 'physical') {
            return $online_access === null;
        }

        return true;
    }

    private function replace_resource_requirements($event_id, $resource_requirements)
    {
        $statement = $this->database->connection()->prepare(
            "DELETE FROM `resource_requirements` WHERE event_id = ?"
        );
        $statement->bindValue(1, $event_id);
        $statement->execute();

        foreach ($resource_requirements as $requirement) {
            $statement = $this->database->connection()->prepare(
                "INSERT INTO `resource_requirements`
                (`event_id`, `resource_id`, `required_quantity`, `notes`)
                VALUES (?, ?, ?, ?)"
            );
            $statement->bindValue(1, $event_id);
            $statement->bindValue(2, $requirement->resource_id);
            $statement->bindValue(3, $requirement->required_quantity);
            $statement->bindValue(4, $requirement->notes);
            $statement->execute();
        }
    }

    private function upsert_online_access($event_id, $online_access)
    {
        $existing_online_access = $this->event_repository->get_online_access($event_id);
        if ($existing_online_access === null) {
            $statement = $this->database->connection()->prepare(
                "INSERT INTO `online_access`
                (`event_id`, `platform`, `meeting_link`, `access_key`, `host_notes`)
                VALUES (?, ?, ?, ?, ?)"
            );
            $statement->bindValue(1, $event_id);
            $statement->bindValue(2, $online_access->platform);
            $statement->bindValue(3, $online_access->meeting_link);
            $statement->bindValue(4, $online_access->access_key);
            $statement->bindValue(5, $online_access->host_notes);
            $statement->execute();

            return;
        }

        $online_access->id = $existing_online_access->id;
        $online_access->event_id = $event_id;
        $this->online_access_repository->update($online_access->id, $online_access);
    }

    private function delete_online_access($event_id)
    {
        $existing_online_access = $this->event_repository->get_online_access($event_id);
        if ($existing_online_access !== null) {
            $this->online_access_repository->delete($existing_online_access->id);
        }
    }

    private function is_pending($event)
    {
        return strtolower(trim((string) $event->status)) === 'pending';
    }
}
