<?php
namespace App\Controllers;

use App\Services\ExamSessionSetupService;

class ExamSessionController extends BaseController
{
    private $exam_service;

    public function __construct($request = [], $view = null, $session = null, $exam_service = null)
    {
        parent::__construct($request, $view, $session);
        $this->exam_service = $exam_service ?? new ExamSessionSetupService();
    }

    public function create()
    {
        if ($guard = $this->guarded(['admin', 'teacher', 'schedule_manager'])) {
            return $guard;
        }

        return $this->respond('events/create', ['type' => 'exam']);
    }

    public function store()
    {
        if ($guard = $this->guarded(['admin', 'teacher', 'schedule_manager'])) {
            return $guard;
        }

        $data = $this->payload();
        $event = $this->exam_service->create_exam_session(
            $data['requestor_id'] ?? $this->request_user_id(),
            $data['subject_name'] ?? '',
            $data['start_time'] ?? '',
            $data['end_time'] ?? '',
            $data['student_count'] ?? 0,
            $data['delivery_mode'] ?? 'physical',
            $data['room_id'] ?? null,
            $data['resource_requirements'] ?? [],
            $data['online_access'] ?? null,
            $data['name'] ?? null
        );

        return $event === null ? $this->error('Exam session could not be created.', 422) : $this->success(['event' => $event], 201);
    }

    public function prepare_hybrid($event_id = null)
    {
        if ($guard = $this->guarded(['admin', 'teacher', 'schedule_manager'])) {
            return $guard;
        }

        $data = $this->payload();
        $event = $this->exam_service->prepare_hybrid_exam(
            $event_id !== null && is_numeric($event_id) ? (int) $event_id : ($data['event_id'] ?? 0),
            $data['room_id'] ?? null,
            $data['platform'] ?? '',
            $data['meeting_link'] ?? ($data['link'] ?? ''),
            $data['access_key'] ?? null,
            $data['resource_requirements'] ?? null,
            $data['host_notes'] ?? ''
        );

        return $event === null ? $this->error('Hybrid exam setup failed.', 422) : $this->success(['event' => $event]);
    }

    public function validate_room()
    {
        if ($guard = $this->guarded(['admin', 'teacher', 'schedule_manager'])) {
            return $guard;
        }

        $data = $this->payload();
        $valid = $this->exam_service->validate_exam_capacity_and_resources(
            $data['room_id'] ?? null,
            $data['start_time'] ?? '',
            $data['end_time'] ?? '',
            $data['student_count'] ?? 0,
            $data['resource_requirements'] ?? []
        );

        return $this->success(['valid' => $valid]);
    }
}
