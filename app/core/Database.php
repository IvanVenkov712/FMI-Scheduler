<?php
namespace App\Core;
use \PDO;

class Database
{
    private $connection;
    public function __construct()
    {
        $host = Config::get('DB_HOST', '127.0.0.1');
        $port = Config::get('DB_PORT', '3306');
        $dbname = Config::get('DB_NAME', 'schedule_management_system');
        $username = Config::get('DB_USER_NAME', Config::get('DB_USER', 'root'));
        $password = Config::get('DB_USER_PASSWORD', Config::get('DB_PASS', ''));
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

        $this->connection = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public function connection()
    {
        return $this->connection;
    }

    public function close() {
        $this->connection = null;
    }

    public function __destruct()
    {
        $this->close();
    }
}
