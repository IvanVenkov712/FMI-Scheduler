<?php
namespace App\Repositories;
use App\Core\Repository;
use App\Models\User;

class UserRepository extends Repository {
    public function __construct($database) {
        parent::__construct($database, User::class, "users");
    }

    public function find_by_username($username) {

    }

    public function find_by_email($email) {

    }

    public function get_requested_events($id) {

    }

    public function get_id_by_username($username) {

    }

    public function get_id_by_email($email) {

    }

    public function get_role($id) {
        
    }
}