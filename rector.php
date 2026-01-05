<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/phpmyfaq/admin/*.php',
        __DIR__ . '/phpmyfaq/services/*.php',
        __DIR__ . '/phpmyfaq/setup/*.php',
        __DIR__ . '/phpmyfaq/src/phpMyFAQ',
        __DIR__ . '/phpmyfaq/*.php',
        __DIR__ . '/tests/*.php',
    ]);

    // register a single rule
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_84,
        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::NAMING,
        SetList::EARLY_RETURN,
        SetList::PHP_84,
        SetList::INSTANCEOF,
        SetList::TYPE_DECLARATION,
        PHPUnitSetList::PHPUNIT_110,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);

    $rectorConfig->skip([
        ReadOnlyPropertyRector::class
    ]);
};
