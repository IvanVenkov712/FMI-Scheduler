<?php
namespace App\Repositories;
use App\Core\Repository;
use App\Models\User;
use App\Models\Event;

class EventRepository extends Repository {
    public function __construct($database) {
        parent::__construct($database, Event::class, 'events');
    }
    public function get_requestor($id) {

    }

    public function get_room($id) {

    }

    public function get_resource_requirements($id) {

    }

    public function get_all_events($start_time, $end_time) {
        
    }
}