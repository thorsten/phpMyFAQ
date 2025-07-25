<?php

/**
 * Private phpMyFAQ Admin API
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-02
 */

use phpMyFAQ\Application;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

require '../../src/Bootstrap.php';

//
// Service Containers
//
$container = new ContainerBuilder();
$loader = new PhpFileLoader($container, new FileLocator(__DIR__));
try {
    $loader->load('../../src/services.php');
} catch (\Exception $e) {
    echo $e->getMessage();
}

$routes = include PMF_SRC_DIR  . '/admin-api-routes.php';

$app = new Application($container);
try {
    $app->run($routes);
} catch (Exception $exception) {
    echo sprintf('Error: %s at line %d at %s', $exception->getMessage(), $exception->getLine(), $exception->getFile());
}
