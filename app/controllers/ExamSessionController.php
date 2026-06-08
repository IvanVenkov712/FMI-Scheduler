<?php
namespace App\Controllers;

use App\Repositories\RoomRepository;
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
        return $this->guarded_response($this->exam_roles(), fn () => $this->respond('events/create', [
            'type' => 'exam',
            'rooms' => (new RoomRepository())->find_all(),
        ]));
    }

    public function store()
    {
        return $this->guarded_response($this->exam_roles(), function () {
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

            return $this->result($event, 'event', 'Exam session could not be created.', 201);
        });
    }

    public function prepare_hybrid($event_id = null)
    {
        return $this->guarded_response($this->exam_roles(), function () use ($event_id) {
            $data = $this->payload();
            $event = $this->exam_service->prepare_hybrid_exam(
                $this->resolve_id($event_id, 'event_id'),
                $data['room_id'] ?? null,
                $data['platform'] ?? '',
                $data['meeting_link'] ?? ($data['link'] ?? ''),
                $data['access_key'] ?? null,
                $data['resource_requirements'] ?? null,
                $data['host_notes'] ?? ''
            );

            return $this->result($event, 'event', 'Hybrid exam setup failed.');
        });
    }

    public function validate_room()
    {
        return $this->guarded_response($this->exam_roles(), function () {
            $data = $this->payload();
            return $this->success([
                'valid' => $this->exam_service->validate_exam_capacity_and_resources(
                    $data['room_id'] ?? null,
                    $data['start_time'] ?? '',
                    $data['end_time'] ?? '',
                    $data['student_count'] ?? 0,
                    $data['resource_requirements'] ?? []
                ),
            ]);
        });
    }

    private function exam_roles()
    {
        return ['admin', 'teacher', 'schedule_manager'];
    }
}
