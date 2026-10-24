<?php

declare(strict_types=1);

// Minimal PSR-4 loader so entry points work without `composer install`.
spl_autoload_register(static function (string $class): void {
    $prefix = 'Biorrhythms\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
