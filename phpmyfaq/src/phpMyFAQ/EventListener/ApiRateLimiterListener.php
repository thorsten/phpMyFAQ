<?php

/**
 * Rate limiter listener for public API requests.
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
 * @since     2026-03-16
 */

declare(strict_types=1);

namespace phpMyFAQ\EventListener;

use phpMyFAQ\Configuration;
use phpMyFAQ\Http\RateLimiter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

readonly class ApiRateLimiterListener
{
    public function __construct(
        private Configuration $configuration,
        private RateLimiter $rateLimiter,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $requestLimit = (int) $this->configuration->get('api.rateLimit.requests');
        $interval = (int) $this->configuration->get('api.rateLimit.interval');

        if ($requestLimit < 1 || $interval < 1) {
            return;
        }

        $request = $event->getRequest();
        $clientIdentifier = $request->getClientIp() ?? 'anonymous';

        if ($this->rateLimiter->check($clientIdentifier, $requestLimit, $interval)) {
            return;
        }

        $response = new JsonResponse(data: [
            'error' => 'Too many requests.',
            'message' => 'Rate limit exceeded. Please retry later.',
        ], status: Response::HTTP_TOO_MANY_REQUESTS);

        foreach ($this->rateLimiter->getHeaders() as $header => $value) {
            $response->headers->set($header, (string) $value);
        }

        $event->setResponse($response);
    }
}
