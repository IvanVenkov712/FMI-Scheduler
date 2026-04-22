<?php

declare(strict_types=1);

class RoomTypeRepository
{
    public function __construct(
        private readonly ?Database $database = null
    ) {
    }

    public function all(): array
    {
        return $this->db()->fetchAll('SELECT * FROM room_types ORDER BY name ASC');
    }

    private function db(): Database
    {
        return $this->database ?? Database::getInstance(Database::configFromFile());
    }
}
