<?php

/**
 * Container-aware Controller Resolver
 *
 * Extends Symfony's ControllerResolver to check if a controller class is registered
 * as a service in the DI container. If yes, returns the pre-configured instance with
 * constructor dependencies resolved. If not, falls back to instantiating with `new`.
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
 * @since     2026-02-16
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller;

use Override;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;

class ContainerControllerResolver extends ControllerResolver
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
        parent::__construct();
    }

    #[Override]
    public function getController(Request $request): callable|false
    {
        $controllerAttr = $request->attributes->get('_controller');

        // If the controller is in ClassName::method format and registered in the container,
        // resolve it from the container BEFORE the parent tries to instantiate with `new`.
        if (is_string($controllerAttr) && str_contains($controllerAttr, '::')) {
            [$class, $method] = explode('::', $controllerAttr, 2);
            if (class_exists($class) && $this->container->has($class)) {
                $instance = $this->container->get($class);
                return [$instance, $method];
            }
        }

        // Fall back to default resolution for unregistered controllers
        return parent::getController($request);
    }
}
