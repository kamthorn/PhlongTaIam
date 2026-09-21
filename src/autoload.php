<?php
declare(strict_types=1);

/**
 * Bootstrap for using this package without Composer, e.g. after copying
 * src/ and data/ somewhere your web server can reach. Composer users get
 * the same mapping from the "autoload" section of composer.json and never
 * need this file.
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'PhlongTaIam\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $path = __DIR__ . '/' . $relative . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});
