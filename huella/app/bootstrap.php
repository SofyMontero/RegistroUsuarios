<?php

date_default_timezone_set('America/Bogota');
set_time_limit(0);

spl_autoload_register(function ($class) {
    $prefix = 'Huella\\';
    $baseDir = __DIR__ . DIRECTORY_SEPARATOR;

    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
