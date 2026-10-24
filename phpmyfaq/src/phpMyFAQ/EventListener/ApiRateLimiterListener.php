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
use phpMyFAQ\Enums\AdminLogType;
use phpMyFAQ\Http\RateLimiter;
use phpMyFAQ\Http\SecurityEventLogger;
use phpMyFAQ\Tenant\TenantContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

readonly class ApiRateLimiterListener
{
    public function __construct(
        private Configuration $configuration,
        private RateLimiter $rateLimiter,
        private TenantContext $tenantContext,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Rate limiting curbs abusive, state-changing requests (login attempts,
        // registration, comments, ...). It must not throttle:
        //  - safe/idempotent reads (GET/HEAD/OPTIONS), e.g., config endpoints, or
        //  - authenticated "private" API routes used by logged-in users.
        if ($request->isMethodSafe()) {
            return;
        }

        $route = (string) $request->attributes->get('_route', '');
        if (str_starts_with($route, 'api.private.')) {
            return;
        }

        $requestLimit = (int) $this->configuration->get('api.rateLimit.requests');
        $interval = (int) $this->configuration->get('api.rateLimit.interval');

        if ($interval < 1) {
            return;
        }

        $clientIdentifier = $request->getClientIp() ?? 'anonymous';

        if ($requestLimit >= 1 && !$this->rateLimiter->check($clientIdentifier, $requestLimit, $interval)) {
            $this->reject($event, $request, sprintf('%d requests per %d seconds exceeded', $requestLimit, $interval));
            return;
        }

        // The tenant quota is a budget shared by all clients, checked after the per-client
        // limit so that a rejected client does not use up the budget of the whole tenant.
        $tenantQuota = $this->tenantContext->getQuotas()->getMaxApiRequests();
        if ($tenantQuota === null) {
            return;
        }

        $tenantKey = sprintf('tenant-%d', $this->tenantContext->getTenantId());
        if ($this->rateLimiter->check($tenantKey, $tenantQuota, $interval)) {
            return;
        }

        $this->reject(
            $event,
            $request,
            sprintf('Tenant quota of %d API requests per %d seconds exceeded', $tenantQuota, $interval),
        );
    }

    private function reject(RequestEvent $event, Request $request, string $reason): void
    {
        new SecurityEventLogger($this->configuration->getLogger())->log(
            AdminLogType::SECURITY_RATE_LIMIT_EXCEEDED,
            $request,
            $reason,
        );

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
