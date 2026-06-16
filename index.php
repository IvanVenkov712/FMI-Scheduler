<?php

define('ROOT', __DIR__);

require ROOT . '/app/core/Autoloader.php';

App\Core\Autoloader::register(ROOT . '/app');
App\Core\Kernel::boot(ROOT)->handle();
