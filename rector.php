<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/phpmyfaq/admin/*.php',
        __DIR__ . '/phpmyfaq/services/*.php',
        __DIR__ . '/phpmyfaq/setup/*.php',
        __DIR__ . '/phpmyfaq/src/phpMyFAQ',
        __DIR__ . '/phpmyfaq/*.php',
    ]);

    // register a single rule
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_80
    ]);
};
