<?php
namespace App\Services;

use App\Helpers\ResourceRequirementsHelper;
use App\Helpers\StringHelper;
use App\Helpers\TimeHelper;
use App\models\Event;
use App\models\OnlineAccess;
use App\Repositories\EventRepository;
use App\Repositories\OnlineAccessRepository;
use App\Repositories\RoomRepository;
use App\Repositories\UserRepository;

class ExamSessionSetupService
{
    private $event_repository;
    private $user_repository;
    private $online_access_repository;
    private $room_repository;
    private $room_availability_service;
    private $room_allocation_service;

    public function __construct(
        $event_repository = null,
        $user_repository = null,
        $online_access_repository = null,
        $room_repository = null,
        $room_availability_service = null,
        $room_allocation_service = null,
    ) {
        $this->event_repository = $event_repository ?? new EventRepository();
        $this->user_repository = $user_repository ?? new UserRepository();
        $this->online_access_repository = $online_access_repository ?? new OnlineAccessRepository();
        $this->room_repository = $room_repository ?? new RoomRepository();
        $this->room_availability_service = $room_availability_service
            ?? new RoomAvailabilityService($this->room_repository, $this->event_repository);
        $this->room_allocation_service = $room_allocation_service
            ?? new RoomAllocationService($this->event_repository, $this->room_repository, $this->room_availability_service);
    }

    public function create_exam_session(
        $requestor_id,
        $subject_name,
        $start_time,
        $end_time,
        $student_count,
        $delivery_mode = 'physical',
        $room_id = null,
        $resource_requirements = [],
        $online_access = null,
        $name = null,
    ) {
        $delivery_mode = StringHelper::normalize_string($delivery_mode);
        $normalized_requirements = ResourceRequirementsHelper::normalize_requirements($resource_requirements);

        if (!$this->can_create_exam($requestor_id, $subject_name, $start_time, $end_time, $student_count, $delivery_mode)) {
            return null;
        }

        if ($normalized_requirements === null) {
            return null;
        }

        if ($this->requires_room($delivery_mode) && $room_id !== null) {
            if (!$this->validate_exam_capacity_and_resources($room_id, $start_time, $end_time, $student_count, $normalized_requirements)
                || !$this->is_room_compatible_with_delivery_mode($room_id, $delivery_mode)) {
                return null;
            }
        }

        if ($delivery_mode === 'online' && $room_id !== null) {
            return null;
        }

        if ($delivery_mode === 'physical' && $online_access !== null) {
            return null;
        }

        if (($delivery_mode === 'online' || $delivery_mode === 'hybrid') && $online_access === null) {
            return null;
        }

        if ($online_access !== null && !$this->is_valid_online_access_payload($online_access)) {
            return null;
        }

        $event = $this->build_exam_event(
            $requestor_id,
            $subject_name,
            $start_time,
            $end_time,
            $student_count,
            $delivery_mode,
            $room_id,
            $name
        );

        $event_id = (int) $this->event_repository->insert($event);
        $this->event_repository->replace_resource_requirements($event_id, $normalized_requirements);

        if ($this->requires_room($delivery_mode) && $room_id === null) {
            if ($this->room_allocation_service->assign_best_room($event_id) === null) {
                $this->event_repository->delete($event_id);
                return null;
            }
        }

        if ($online_access !== null) {
            if (!$this->attach_online_access_from_payload($event_id, $online_access)) {
                $this->event_repository->delete($event_id);
                return null;
            }
        }

        return $this->event_repository->find_by_id($event_id);
    }

    public function prepare_hybrid_exam(
        $event_id,
        $room_id,
        $platform,
        $meeting_link,
        $access_key = null,
        $resource_requirements = null,
        $host_notes = '',
    ) {
        $event = $this->event_repository->find_by_id($event_id);
        if ($event === null || StringHelper::normalize_string($event->type) !== 'exam') {
            return null;
        }

        $requirements = $resource_requirements === null
            ? $this->event_repository->get_resource_requirements($event_id)
            : ResourceRequirementsHelper::normalize_requirements($resource_requirements);

        if ($requirements === null) {
            return null;
        }

        $online_access = $this->build_online_access($platform, $meeting_link, $access_key, $host_notes);
        if (!$this->is_valid_online_access_payload($online_access)) {
            return null;
        }

        if (!$this->validate_exam_capacity_and_resources($room_id, $event->start_time, $event->end_time, $event->student_count, $requirements)
            || !$this->is_room_compatible_with_delivery_mode($room_id, 'hybrid')) {
            return null;
        }

        $event->delivery_mode = 'hybrid';
        $event->room_id = $room_id;
        $this->event_repository->update($event_id, $event);
        $this->event_repository->replace_resource_requirements($event_id, $requirements);

        if (!$this->attach_online_access_from_payload($event_id, $online_access)) {
            return null;
        }

        return $this->event_repository->find_by_id($event_id);
    }

    public function validate_exam_capacity_and_resources($room_id, $start_time, $end_time, $student_count, $resource_requirements = [])
    {
        $requirements = ResourceRequirementsHelper::normalize_requirements($resource_requirements);
        if ($requirements === null) {
            return false;
        }

        if (!is_numeric($student_count) || (int) $student_count < 0) {
            return false;
        }

        return $this->room_availability_service->is_room_bookable(
            $room_id,
            $start_time,
            $end_time,
            (int) $student_count,
            $requirements
        );
    }

    private function can_create_exam($requestor_id, $subject_name, $start_time, $end_time, $student_count, $delivery_mode)
    {
        if (!is_numeric($requestor_id) || !$this->user_repository->exists($requestor_id)) {
            return false;
        }

        if (trim((string) $subject_name) === '') {
            return false;
        }

        if (!TimeHelper::is_valid_interval($start_time, $end_time)) {
            return false;
        }

        if (!is_numeric($student_count) || (int) $student_count < 0) {
            return false;
        }

        return in_array($delivery_mode, ['physical', 'online', 'hybrid'], true);
    }

    private function build_exam_event(
        $requestor_id,
        $subject_name,
        $start_time,
        $end_time,
        $student_count,
        $delivery_mode,
        $room_id,
        $name
    ) {
        $event = new Event();
        $event->requestor_id = (int) $requestor_id;
        $event->room_id = $room_id === null ? null : (int) $room_id;
        $event->name = trim((string) ($name ?? ('Exam: ' . $subject_name)));
        $event->type = 'exam';
        $event->start_time = $start_time;
        $event->end_time = $end_time;
        $event->delivery_mode = $delivery_mode;
        $event->subject_name = trim((string) $subject_name);
        $event->student_count = (int) $student_count;
        $event->status = 'pending';

        return $event;
    }
    
    private function attach_online_access_from_payload($event_id, $online_access)
    {
        $online_access = is_array($online_access)
            ? $this->build_online_access_from_array($online_access)
            : $online_access;

        if ($online_access === null) {
            return false;
        }

        if (!$this->is_valid_online_access_for_event($event_id, $online_access)) {
            return false;
        }

        $event = $this->event_repository->find_by_id($event_id);
        if ($event->online_access_id !== null) {
            $online_access->id = $event->online_access_id;
            $this->online_access_repository->update($event->online_access_id, $online_access);

            return $this->online_access_repository->find_by_id($event->online_access_id) !== null;
        }

        $this->event_repository->upsert_online_access($event_id, $online_access);

        return $this->event_repository->get_online_access($event_id) !== null;
    }

    private function build_online_access_from_array($payload)
    {
        return $this->build_online_access(
            $payload['platform'] ?? '',
            $payload['meeting_link'] ?? ($payload['link'] ?? ''),
            $payload['access_key'] ?? null,
            $payload['host_notes'] ?? ''
        );
    }

    private function build_online_access($platform, $meeting_link, $access_key = null, $host_notes = '')
    {
        $online_access = new OnlineAccess();
        $online_access->platform = trim((string) $platform);
        $online_access->meeting_link = trim((string) $meeting_link);
        $online_access->access_key = $access_key === null ? bin2hex(random_bytes(16)) : trim((string) $access_key);
        $online_access->host_notes = trim((string) $host_notes);

        return $online_access;
    }

    private function is_valid_online_access_for_event($event_id, $online_access)
    {
        $event = $this->event_repository->find_by_id($event_id);
        if ($event === null) {
            return false;
        }

        if (!in_array(StringHelper::normalize_string($event->delivery_mode), ['online', 'hybrid'], true)) {
            return false;
        }

        return $this->is_valid_online_access_payload($online_access);
    }

    private function is_valid_online_access_payload($online_access)
    {
        $online_access = is_array($online_access)
            ? $this->build_online_access_from_array($online_access)
            : $online_access;

        if ($online_access === null || StringHelper::normalize_string($online_access->platform) === '') {
            return false;
        }

        return filter_var($online_access->meeting_link, FILTER_VALIDATE_URL) !== false;
    }

    private function requires_room($delivery_mode)
    {
        return $delivery_mode === 'physical' || $delivery_mode === 'hybrid';
    }

    private function is_room_compatible_with_delivery_mode($room_id, $delivery_mode)
    {
        $room = $this->room_repository->find_by_id($room_id);
        if ($room === null || !$room->is_active) {
            return false;
        }

        $room_type = StringHelper::normalize_string($room->room_type);
        if ($delivery_mode === 'physical' || $delivery_mode === 'hybrid') {
            return $room_type === 'physical' || $room_type === 'hybrid';
        }

        return false;
    }
}
