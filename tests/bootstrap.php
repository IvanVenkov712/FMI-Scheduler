<?php

declare(strict_types=1);

$root = dirname(__DIR__);

foreach (glob($root . '/config/*.php') as $configFile) {
    $candidate = $configFile;
    if (is_file($candidate)) {
        require_once $candidate;
    }
}

spl_autoload_register(static function (string $class) use ($root): void {
    $class = ltrim($class, '\\');
    $relativeClass = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

    $directories = [
        'app/core',
        'app/controllers',
        'app/models',
        'app/services',
        'app/repositories',
        'app/middleware',
        'app/helpers',
        'tests/Support',
    ];

    foreach ($directories as $directory) {
        $path = $root . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . basename($relativeClass);
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }
});
