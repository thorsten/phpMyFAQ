<?php

/**
 * Router listener for matching requests to routes
 *
 * Matches incoming requests against the route collection and sets request attributes.
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

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class RouterListener
{
    public function __construct(
        private readonly RouteCollection $routes,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Skip if already matched (e.g., by sub-request or test)
        if ($request->attributes->has('_controller')) {
            return;
        }

        $requestContext = new RequestContext();
        $requestContext->fromRequest($request);

        $urlMatcher = new UrlMatcher($this->routes, $requestContext);
        $parameters = $urlMatcher->match($request->getPathInfo());
        $request->attributes->add($parameters);
    }
}
