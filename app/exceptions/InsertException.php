<?php
namespace App\Exceptions;
use \Exception;

class InsertException extends Exception {
    public function __construct($message = "", $code = 0) {
        parent::__construct($message, $code);
    }
}