<?php

use phpMyFAQ\Application;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\WebAuthnController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

require '../../src/Bootstrap.php';

$faqConfig = Configuration::getConfigurationInstance();

$routes = new RouteCollection();
$routes->add(
    'public.webauthn.index',
    new Route('/', ['_controller' => [WebAuthnController::class, 'index']])
);

$app = new Application($faqConfig);
try {
    $app->run($routes);
} catch (Exception $exception) {
    echo $exception->getMessage();
}
