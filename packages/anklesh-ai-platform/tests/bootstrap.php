<?php

declare(strict_types=1);

require dirname(__DIR__, 3).'/vendor/autoload.php';

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'Anklesh\\AiPlatform\\Tests\\' => __DIR__.'/',
        'Anklesh\\AiPlatform\\' => dirname(__DIR__).'/src/',
    ];

    foreach ($prefixes as $prefix => $directory) {
        if (! str_starts_with($class, $prefix)) {
            continue;
        }

        $relativeClass = substr($class, strlen($prefix));
        $file = $directory.str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass).'.php';

        if (is_file($file)) {
            require $file;
        }
    }
});
