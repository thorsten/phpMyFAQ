<?php

use phpMyFAQ\Application;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\WebAuthnController;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

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

$routes = new RouteCollection();
$routes->add(
    'public.webauthn.index',
    new Route('/', ['_controller' => [WebAuthnController::class, 'index']])
);

$app = new Application($container);
try {
    $app->run($routes);
} catch (Exception $exception) {
    echo $exception->getMessage();
}
