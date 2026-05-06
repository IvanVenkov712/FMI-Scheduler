<?php
namespace App\Services;

use App\Helpers\StringHelper;
use App\Repositories\EventRepository;
use App\Repositories\UserRepository;

class UserEventAccessService
{
    private $user_repository;
    private $event_repository;

    private const EVENT_MANAGER_ROLES = ['admin', 'schedule_manager'];
    private const EVENT_APPROVER_ROLES = ['admin', 'schedule_manager'];

    public function __construct($user_repository = null, $event_repository = null)
    {
        $this->user_repository = $user_repository ?? new UserRepository();
        $this->event_repository = $event_repository ?? new EventRepository();
    }

    public function can_user_manage_event($user_id, $event_id)
    {
        if (!$this->has_valid_ids($user_id, $event_id)) {
            return false;
        }

        $event = $this->event_repository->find_by_id($event_id);
        if ($event === null) {
            return false;
        }

        $role = $this->get_user_role($user_id);
        if ($this->has_any_role($role, self::EVENT_MANAGER_ROLES)) {
            return true;
        }

        return (int) $event->requestor_id === (int) $user_id;
    }

    public function can_user_approve_event($user_id, $event_id)
    {
        if (!$this->has_valid_ids($user_id, $event_id)) {
            return false;
        }

        $event = $this->event_repository->find_by_id($event_id);
        if ($event === null) {
            return false;
        }

        $role = $this->get_user_role($user_id);
        if (!$this->has_any_role($role, self::EVENT_APPROVER_ROLES)) {
            return false;
        }

        return (int) $event->requestor_id !== (int) $user_id;
    }

    public function list_manageable_events($user_id)
    {
        if (!is_numeric($user_id)) {
            return [];
        }

        $role = $this->get_user_role($user_id);
        if ($role === null) {
            return [];
        }

        if ($this->has_any_role($role, self::EVENT_MANAGER_ROLES)) {
            return $this->event_repository->find_all();
        }

        return $this->user_repository->get_requested_events((int) $user_id);
    }

    public function list_approvable_events($user_id)
    {
        if (!is_numeric($user_id)) {
            return [];
        }

        $role = $this->get_user_role($user_id);
        if (!$this->has_any_role($role, self::EVENT_APPROVER_ROLES)) {
            return [];
        }

        return array_values(array_filter(
            $this->event_repository->find_all(),
            fn ($event) => $event->is_pending()
        ));
    }

    private function has_valid_ids($user_id, $event_id)
    {
        return is_numeric($user_id) && is_numeric($event_id);
    }

    private function get_user_role($user_id)
    {
        $role = $this->user_repository->get_role((int) $user_id);
        $role = StringHelper::normalize_string($role);

        return $role === '' ? null : $role;
    }

    private function has_any_role($role, $allowed_roles)
    {
        if ($role === null) {
            return false;
        }

        return in_array($role, $allowed_roles, true);
    }
}
