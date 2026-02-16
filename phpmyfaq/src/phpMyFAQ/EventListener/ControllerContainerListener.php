<?php

/**
 * Controller container injection listener
 *
 * Injects the shared DI container into controllers that extend AbstractController.
 * This replaces the per-controller container creation.
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
 * @since     2026-02-15
 */

declare(strict_types=1);

namespace phpMyFAQ\EventListener;

use phpMyFAQ\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class ControllerContainerListener
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        // Handle array-style callables [object, method]
        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if ($controller instanceof AbstractController) {
            $controller->setContainer($this->container);
        }
    }
}
