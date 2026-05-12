<?php
namespace App\Controllers;

use App\Services\RoomAllocationService;
use App\Services\RoomAvailabilityService;

class AllocationController extends BaseController
{
    private $allocation_service;
    private $availability_service;

    public function __construct($request = [], $view = null, $session = null, $allocation_service = null, $availability_service = null)
    {
        parent::__construct($request, $view, $session);
        $this->allocation_service = $allocation_service ?? new RoomAllocationService();
        $this->availability_service = $availability_service ?? new RoomAvailabilityService();
    }

    public function search()
    {
        return $this->guarded_response(null, function () {
            $data = $this->payload();
            return $this->respond('allocation/search', [
                'rooms' => $this->availability_service->find_eligible_rooms(
                    $data['start_time'] ?? '',
                    $data['end_time'] ?? '',
                    $data['student_count'] ?? 0,
                    $data['resource_requirements'] ?? []
                ),
            ]);
        });
    }

    public function search_form()
    {
        return $this->guarded_response(null, fn () => $this->respond('allocation/search', []));
    }

    public function candidates($event_id = null)
    {
        return $this->guarded_response(null, fn () => $this->success([
            'candidates' => $this->allocation_service->rank_candidate_rooms($this->resolve_event_id($event_id)),
        ]));
    }

    public function assign($event_id = null)
    {
        return $this->guarded_response(['admin', 'schedule_manager'], function () use ($event_id) {
            $event = $this->allocation_service->assign_best_room($this->resolve_event_id($event_id));
            return $event === null ? $this->error('Could not assign a room.', 422) : $this->success(['event' => $event]);
        });
    }

    public function reassign($event_id = null)
    {
        return $this->guarded_response(['admin', 'schedule_manager'], function () use ($event_id) {
            $data = $this->payload();
            $event = $this->allocation_service->reassign_room(
                $this->resolve_event_id($event_id),
                $data['preferred_room_id'] ?? null
            );

            return $event === null ? $this->error('Could not reassign the room.', 422) : $this->success(['event' => $event]);
        });
    }

    public function auto_assign($event_id = null)
    {
        return $this->assign($event_id);
    }

    private function resolve_event_id($event_id)
    {
        if ($event_id !== null && is_numeric($event_id)) {
            return (int) $event_id;
        }

        return $this->int_param('event_id', $this->payload()['event_id'] ?? 0);
    }
}
