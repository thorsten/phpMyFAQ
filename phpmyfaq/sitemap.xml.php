<?php

/**
 * The dynamic Google Sitemap builder.
 *
 * The Google Sitemap protocol is described here:
 * https://www.google.com/webmasters/sitemaps/docs/en/protocol.html
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2006-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-06-26
 */

use phpMyFAQ\Application;
use phpMyFAQ\Controller\SitemapController;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

require './src/Bootstrap.php';

//
// Service Containers
//
$container = new ContainerBuilder();
$loader = new PhpFileLoader($container, new FileLocator(__DIR__));
try {
    $loader->load('src/services.php');
} catch (\Exception $e) {
    echo $e->getMessage();
}

$routes = new RouteCollection();
$routes->add('public.sitemap.xml', new Route('/sitemap.xml', ['_controller' => [SitemapController::class, 'index']]));

$app = new Application($container);
try {
    $app->run($routes);
} catch (Exception $exception) {
    echo $exception->getMessage();
}
