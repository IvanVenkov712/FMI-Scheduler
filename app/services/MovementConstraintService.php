<?php
namespace App\Services;

use App\Helpers\StringHelper;
use App\Helpers\TimeHelper;
use App\Repositories\MovementConstraintRepository;
use App\Repositories\RouteSegmentRepository;

class MovementConstraintService extends BaseService
{
    private $route_segment_repository;
    private $movement_constraint_repository;
    private $route_segment_service;

    public function __construct(
        $route_segment_repository = null,
        $movement_constraint_repository = null,
        $route_segment_service = null
    ) {
        $this->route_segment_repository = $this->dependency($route_segment_repository, RouteSegmentRepository::class);
        $this->movement_constraint_repository = $this->dependency($movement_constraint_repository, MovementConstraintRepository::class);
        $this->route_segment_service = $this->dependency($route_segment_service, RouteSegmentService::class, $this->route_segment_repository);
    }

    public function is_route_segment_usable($segmentId, $start_time, $end_time)
    {
        if (!TimeHelper::is_valid_interval($start_time, $end_time)) {
            return false;
        }

        $segment = $this->route_segment_repository->find_by_id($segmentId);
        if ($segment === null || !$segment->is_available) {
            return false;
        }

        foreach ($this->get_overlapping_constraints($segment->id, $start_time, $end_time) as $constraint) {
            if ($this->is_blocking_constraint($constraint)) {
                return false;
            }

            if ($this->is_capacity_constraint($constraint) && (int) $constraint->max_capacity <= 0) {
                return false;
            }
        }

        return true;
    }

    public function is_valid_movement_plan($route, $start_time, $end_time)
    {
        if (!TimeHelper::is_valid_interval($start_time, $end_time) || !is_array($route)) {
            return false;
        }

        if ($this->has_capacity_violation($route, $start_time, $end_time)) {
            return false;
        }

        $previous_segment = null;
        foreach ($route as $route_item) {
            $segment = $this->route_segment_service->resolve_route_segment($route_item);
            if ($segment === null || !$this->is_route_segment_usable($segment->id, $start_time, $end_time)) {
                return false;
            }

            if ($previous_segment !== null && (int) $previous_segment->to_room_id !== (int) $segment->from_room_id) {
                return false;
            }

            $previous_segment = $segment;
        }

        return true;
    }

    /**
     * Compares a supplied group's size to each segment's effective capacity.
     * Independent journeys are not aggregated; unspecified movement size is one.
     */
    public function has_capacity_violation($route, $start_time, $end_time)
    {
        if (!TimeHelper::is_valid_interval($start_time, $end_time) || !is_array($route)) {
            return false;
        }

        foreach ($route as $route_item) {
            $segment = $this->route_segment_service->resolve_route_segment($route_item);
            if ($segment === null) {
                return true;
            }

            $movement_size = $this->resolve_movement_size($route_item);
            $effective_capacity = $this->resolve_effective_capacity($segment, $start_time, $end_time);

            if ($effective_capacity !== null && $movement_size > $effective_capacity) {
                return true;
            }
        }

        return false;
    }

    private function get_overlapping_constraints($segment_id, $start_time, $end_time)
    {
        return $this->movement_constraint_repository->find_overlapping_for_segment($segment_id, $start_time, $end_time);
    }

    private function resolve_movement_size($route_item)
    {
        $candidate_keys = ['movement_size', 'student_count', 'people_count', 'group_size', 'quantity'];

        foreach ($candidate_keys as $key) {
            if (is_array($route_item) && array_key_exists($key, $route_item) && is_numeric($route_item[$key])) {
                return max(0, (int) $route_item[$key]);
            }

            if (is_object($route_item) && isset($route_item->$key) && is_numeric($route_item->$key)) {
                return max(0, (int) $route_item->$key);
            }
        }

        return 1;
    }

    private function resolve_effective_capacity($segment, $start_time, $end_time)
    {
        $capacities = [];

        if ((int) $segment->corridor_capacity > 0) {
            $capacities[] = (int) $segment->corridor_capacity;
        }

        foreach ($this->get_overlapping_constraints($segment->id, $start_time, $end_time) as $constraint) {
            if (!$this->is_capacity_constraint($constraint)) {
                continue;
            }

            $capacities[] = (int) $constraint->max_capacity;
        }

        return $capacities === [] ? null : min($capacities);
    }

    private function is_blocking_constraint($constraint)
    {
        $constraint_type = StringHelper::normalize_string($constraint->constraint_type);
        $blocking_types = [
            'blocked',
            'closed',
            'closure',
            'maintenance',
            'unavailable',
            'emergency',
            'pandemic',
            'no_entry',
        ];

        return in_array($constraint_type, $blocking_types, true);
    }

    private function is_capacity_constraint($constraint)
    {
        $constraint_type = StringHelper::normalize_string($constraint->constraint_type);
        $capacity_types = [
            'capacity',
            'capacity_limit',
            'limited_capacity',
            'reduced_capacity',
            'max_capacity',
        ];

        return in_array($constraint_type, $capacity_types, true);
    }
}
