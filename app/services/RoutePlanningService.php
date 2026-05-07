<?php
namespace App\Services;

use App\Repositories\RoomRepository;
use App\Repositories\RouteSegmentRepository;

class RoutePlanningService extends BaseService
{
    private $room_repository;
    private $route_segment_repository;
    private $movement_constraint_service;
    private $route_segment_service;

    public function __construct(
        $room_repository = null,
        $route_segment_repository = null,
        $movement_constraint_service = null,
        $route_segment_service = null
    ) {
        $this->room_repository = $this->dependency($room_repository, RoomRepository::class);
        $this->route_segment_repository = $this->dependency($route_segment_repository, RouteSegmentRepository::class);
        $this->movement_constraint_service = $movement_constraint_service
            ?? new MovementConstraintService($this->route_segment_repository);
        $this->route_segment_service = $this->dependency($route_segment_service, RouteSegmentService::class, $this->route_segment_repository);
    }

    public function find_route($fromRoomId, $toRoomId, $start_time, $end_time)
    {
        return $this->find_shortest_route($fromRoomId, $toRoomId, false, $start_time, $end_time);
    }

    public function find_accessible_route($fromRoomId, $toRoomId, $start_time, $end_time)
    {
        if (($start_time === null) !== ($end_time === null)) {
            return null;
        }

        return $this->find_shortest_route($fromRoomId, $toRoomId, true, $start_time, $end_time);
    }

    public function get_adjacent_rooms($roomId, $accessible_only, $start_time, $end_time)
    {
        if(!$this->is_valid_room_id($roomId)) {
            return null;
        }
        $adjacent_rooms = [];
        foreach ($this->get_available_outgoing_segments($roomId, $accessible_only, $start_time, $end_time) as $segment) {
            $room = $this->room_repository->find_by_id($segment->to_room_id);
            if ($room !== null && $room->is_active) {
                $adjacent_rooms[] = $room;
            }
        }

        return $adjacent_rooms;
    }

    public function get_route_distance($route)
    {
        if (!is_array($route)) {
            return null;
        }

        $distance = 0.0;
        foreach ($route as $route_item) {
            $segment = $this->route_segment_service->resolve_route_segment($route_item);
            if ($segment === null) {
                return null;
            }

            $distance += max(0, (float) $segment->distance);
        }

        return $distance;
    }

    private function find_shortest_route(
        $from_room_id,
        $to_room_id,
        $accessible_only,
        $start_time,
        $end_time,
    ) {
        if (!$this->is_valid_room_id($from_room_id) || !$this->is_valid_room_id($to_room_id)) {
            return null;
        }

        $from_room_id = (int) $from_room_id;
        $to_room_id = (int) $to_room_id;

        if ($from_room_id === $to_room_id) {
            return [];
        }

        $distances = [$from_room_id => 0.0];
        $previous_segments = [];
        $visited = [];
        $queue = [$from_room_id => 0.0];

        while ($queue !== []) {
            $current_room_id = $this->pop_nearest_room_id($queue);

            if (isset($visited[$current_room_id])) {
                continue;
            }

            $visited[$current_room_id] = true;

            if ((int) $current_room_id === $to_room_id) {
                return $this->build_route($previous_segments, $from_room_id, $to_room_id);
            }

            foreach ($this->get_available_outgoing_segments($current_room_id, $accessible_only, $start_time, $end_time) as $segment) {
                $next_room_id = (int) $segment->to_room_id;

                if (isset($visited[$next_room_id])) {
                    continue;
                }

                $candidate_distance = $distances[$current_room_id] + max(0, (float) $segment->distance);
                if (!isset($distances[$next_room_id]) || $candidate_distance < $distances[$next_room_id]) {
                    $distances[$next_room_id] = $candidate_distance;
                    $previous_segments[$next_room_id] = $segment;
                    $queue[$next_room_id] = $candidate_distance;
                }
            }
        }

        return null;
    }

    private function get_available_outgoing_segments(
        $room_id,
        $accessible_only,
        $start_time,
        $end_time,
    ) {
        $segments = [];

        foreach ($this->room_repository->get_outgoing_route_segments($room_id) as $segment) {
            if (!$segment->is_available) {
                continue;
            }

            if ($accessible_only && $segment->is_stairs) {
                continue;
            }

            if (
                $accessible_only
                && $start_time !== null
                && $end_time !== null
                && !$this->movement_constraint_service->is_route_segment_usable($segment->id, $start_time, $end_time)
            ) {
                continue;
            }

            $to_room = $this->room_repository->find_by_id($segment->to_room_id);
            if ($to_room === null || !$to_room->is_active) {
                continue;
            }

            $segments[] = $segment;
        }

        return $segments;
    }

    private function build_route($previous_segments, $from_room_id, $to_room_id)
    {
        $route = [];
        $current_room_id = $to_room_id;

        while ((int) $current_room_id !== (int) $from_room_id) {
            if (!isset($previous_segments[$current_room_id])) {
                return null;
            }

            $segment = $previous_segments[$current_room_id];
            array_unshift($route, $segment);
            $current_room_id = (int) $segment->from_room_id;
        }

        return $route;
    }

    private function pop_nearest_room_id(&$queue)
    {
        $nearest_room_id = null;
        $nearest_distance = null;

        foreach ($queue as $room_id => $distance) {
            if ($nearest_distance === null || $distance < $nearest_distance) {
                $nearest_room_id = $room_id;
                $nearest_distance = $distance;
            }
        }

        unset($queue[$nearest_room_id]);
        return (int) $nearest_room_id;
    }

    private function is_valid_room_id($room_id)
    {
        return is_numeric($room_id) && $this->room_repository->exists($room_id);
    }
}
