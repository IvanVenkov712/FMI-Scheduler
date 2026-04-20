<?php

declare(strict_types=1);

class Database
{
    private static ?self $instance = null;
    private ?PDO $connection = null;
    private array $config;

    private function __construct(array $config = [])
    {
        $this->config = array_replace_recursive($this->defaultConfig(), $config);
    }

    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public static function connection(): PDO
    {
        return self::getInstance()->pdo();
    }

    public function pdo(): PDO
    {
        if ($this->connection === null) {
            $driver = $this->config['driver'];
            $charset = $this->config['charset'];

            if ($driver === 'sqlite') {
                $dsn = 'sqlite:' . $this->config['database'];
            } else {
                $dsn = sprintf(
                    '%s:host=%s;port=%s;dbname=%s;charset=%s',
                    $driver,
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['database'],
                    $charset
                );
            }

            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
        }

        return $this->connection;
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->pdo()->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();

        return $result === false ? null : $result;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function insert(string $sql, array $params = []): string
    {
        $this->query($sql, $params);

        return $this->pdo()->lastInsertId();
    }

    public function transaction(callable $callback): mixed
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $result = $callback($this);
            $pdo->commit();

            return $result;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    public static function configFromFile(?string $path = null): array
    {
        $path ??= dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

        if (!is_file($path)) {
            return [];
        }

        $config = require $path;

        return is_array($config) ? $config : [];
    }

    private function defaultConfig(): array
    {
        return [
            'driver' => getenv('DB_DRIVER') ?: 'mysql',
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => getenv('DB_PORT') ?: '3306',
            'database' => getenv('DB_DATABASE') ?: 'schedule_management_system',
            'username' => getenv('DB_USERNAME') ?: 'root',
            'password' => getenv('DB_PASSWORD') ?: '',
            'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ];
    }
}
