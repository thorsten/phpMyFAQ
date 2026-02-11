#!/usr/bin/env php
<?php

/**
 * phpMyFAQ background worker.
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
 * @since     2026-02-11
 */

declare(strict_types=1);

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

require __DIR__ . '/../phpmyfaq/src/Bootstrap.php';
require __DIR__ . '/../phpmyfaq/src/autoload.php';

$maxJobs = isset($argv[1]) ? max(0, (int) $argv[1]) : 0;

$container = new ContainerBuilder();
$loader = new PhpFileLoader($container, new FileLocator(__DIR__));
$loader->load('../phpmyfaq/src/services.php');
$container->compile();

$worker = $container->get('phpmyfaq.queue.worker');
$processed = $worker->run($maxJobs);

echo sprintf("Processed %d job(s).\n", $processed);

