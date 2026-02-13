#!/usr/bin/env php
<?php

/**
 * phpMyFAQ task scheduler.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-13
 */

declare(strict_types=1);

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

require __DIR__ . '/../phpmyfaq/src/Bootstrap.php';
require __DIR__ . '/../phpmyfaq/src/autoload.php';

$command = $argv[1] ?? '';
if ($command !== 'run') {
    fwrite(STDERR, "Usage: php bin/scheduler.php run\n");
    exit(1);
}

$container = new ContainerBuilder();
$loader = new PhpFileLoader($container, new FileLocator(__DIR__));
$loader->load('../phpmyfaq/src/services.php');
$container->compile();

$scheduler = $container->get('phpmyfaq.scheduler.task-scheduler');
$results = $scheduler->run();

echo "Task scheduler finished.\n";
echo json_encode($results, JSON_PRETTY_PRINT) . PHP_EOL;
