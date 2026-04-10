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
use phpMyFAQ\Controller\Administration\SkipsAuthenticationCheck;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

readonly class ControllerContainerListener
{
    private const string ADMIN_NAMESPACE_PREFIX = 'phpMyFAQ\\Controller\\Administration\\';

    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if (!$controller instanceof AbstractController) {
            return;
        }

        $controller->setContainer($this->container);

        if ($this->requiresAdminAuthentication($controller)) {
            $controller->userIsAuthenticated();
        }
    }

    /**
     * Defense-in-depth: every controller in the Administration namespace must
     * have an authenticated user, except controllers that explicitly opt out by
     * implementing SkipsAuthenticationCheck (e.g. AuthenticationController,
     * which handles login/logout/token endpoints).
     */
    private function requiresAdminAuthentication(AbstractController $controller): bool
    {
        if ($controller instanceof SkipsAuthenticationCheck) {
            return false;
        }

        return str_starts_with($controller::class, self::ADMIN_NAMESPACE_PREFIX);
    }
}
