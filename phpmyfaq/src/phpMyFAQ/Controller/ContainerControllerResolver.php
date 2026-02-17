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

    #[\Override]
    public function getController(Request $request): callable|false
    {
        $controller = parent::getController($request);

        if ($controller === false) {
            return false;
        }

        // Handle array-style callables [object, method]
        if (is_array($controller) && isset($controller[0]) && is_object($controller[0])) {
            $controllerClass = $controller[0]::class;

            if ($this->container->has($controllerClass)) {
                $controller[0] = $this->container->get($controllerClass);
            }

            return $controller;
        }

        return $controller;
    }
}
