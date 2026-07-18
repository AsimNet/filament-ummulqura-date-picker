<?php

declare(strict_types=1);

/*
 * Prefers the package's own vendor directory (standalone repo after
 * `composer install`), falling back to the host application's autoloader
 * when the package lives in the monorepo's packages/ directory.
 */
foreach ([__DIR__.'/../vendor/autoload.php', __DIR__.'/../../../vendor/autoload.php'] as $autoloader) {
    if (file_exists($autoloader)) {
        require_once $autoloader;

        break;
    }
}
