<?php

/**
 * The Admin Dashboard Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-15
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use Exception;
use JsonException;
use phpMyFAQ\Administration\DashboardLayout;
use phpMyFAQ\Administration\Faq as AdminFaq;
use phpMyFAQ\Administration\RemoteApiClient;
use phpMyFAQ\Administration\Session as AdminSession;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Faq;
use phpMyFAQ\Search;
use phpMyFAQ\Session\Token;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class DashboardController extends AbstractController
{
    /**
     * How long a cached remote response is considered fresh, in seconds.
     */
    private const REMOTE_CACHE_TTL = 3600;

    /**
     * How long a cached remote response is retained for stale-on-error fallback, in seconds.
     */
    private const REMOTE_CACHE_RETENTION = 86_400;

    /**
     * Widget keys that may appear in a stored dashboard layout. Anything else is dropped.
     */
    private const ALLOWED_WIDGETS = [
        'inactive-faqs',
        'recent-users',
        'content-health',
        'popular-searches',
        'version-check',
        'verification-check',
        'backup-status',
        'sponsor',
        'support',
    ];

    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly CacheItemPoolInterface $cache,
    ) {
        parent::__construct();
    }

    /**
     * Returns a still-fresh cached payload for the given key, or null when none exists.
     *
     * @return array<string, mixed>|null
     * @throws InvalidArgumentException
     */
    private function getFreshCache(string $key): ?array
    {
        $item = $this->cache->getItem($key);
        if (!$item->isHit()) {
            return null;
        }

        $cached = $item->get();
        if (!is_array($cached) || !array_key_exists('fetchedAt', $cached) || !array_key_exists('payload', $cached)) {
            return null;
        }

        if ((time() - (int) $cached['fetchedAt']) > self::REMOTE_CACHE_TTL) {
            return null;
        }

        if (!is_array($cached['payload'])) {
            return null;
        }

        return $cached['payload'];
    }

    /**
     * Returns any cached payload for the given key regardless of age (stale-on-error fallback).
     *
     * @return array<string, mixed>|null
     * @throws InvalidArgumentException
     */
    private function getStaleCache(string $key): ?array
    {
        $cached = $this->cache->getItem($key)->get();
        if (is_array($cached) && array_key_exists('payload', $cached) && is_array($cached['payload'])) {
            return $cached['payload'];
        }

        return null;
    }

    /**
     * Stores a remote payload together with its fetch timestamp.
     *
     * @param array<string, mixed> $payload
     * @throws InvalidArgumentException
     */
    private function storeCache(string $key, array $payload): void
    {
        $item = $this->cache->getItem($key);
        $item->set(['fetchedAt' => time(), 'payload' => $payload]);
        $item->expiresAfter(self::REMOTE_CACHE_RETENTION);
        $this->cache->save($item);
    }

    /**
     * @throws JsonException
     */
    #[Route(path: 'dashboard/verify', name: 'admin.api.dashboard.verify', methods: ['POST'])]
    public function verify(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $data = $request->getContent();
        $api = new RemoteApiClient($this->configuration, new System());

        return $this->json($api->setRemoteHashes($data)->getVerificationIssues());
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'dashboard/versions', name: 'admin.api.dashboard.versions', methods: ['GET'])]
    public function versions(): JsonResponse
    {
        $this->userIsAuthenticated();

        $releaseEnvironment = $this->configuration->get(item: 'upgrade.releaseEnvironment');
        $cacheKey = 'dashboard.versions.' . $releaseEnvironment;

        $fresh = $this->getFreshCache($cacheKey);
        if ($fresh !== null) {
            return $this->json($fresh);
        }

        $api = new RemoteApiClient($this->configuration, new System());

        try {
            $versions = $api->getVersions();
            if (!array_key_exists('installed', $versions) || !array_key_exists($releaseEnvironment, $versions)) {
                throw new Exception('Version lookup failed for release environment "' . $releaseEnvironment . '".');
            }

            $info = [];
            if (version_compare($versions['installed'], $versions[$releaseEnvironment]) < 0) {
                $info = ['warning' => Translation::get(key: 'ad_you_should_update')];
            }

            if (version_compare($versions['installed'], $versions[$releaseEnvironment]) >= 0) {
                $info = [
                    'success' =>
                        Translation::getString(key: 'ad_xmlrpc_latest') . ': phpMyFAQ ' . ($versions['stable'] ?? ''),
                ];
            }

            $this->storeCache($cacheKey, $info);

            return $this->json($info);
        } catch (DecodingExceptionInterface|TransportExceptionInterface|Exception $exception) {
            $stale = $this->getStaleCache($cacheKey);
            if ($stale !== null) {
                return $this->json($stale);
            }

            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'dashboard/visits', name: 'admin.api.dashboard.visits', methods: ['GET'])]
    public function visits(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        if ($this->configuration->get(item: 'main.enableUserTracking')) {
            $requestTime = (int) $request->server->get('REQUEST_TIME');
            $endDate = $requestTime !== 0 ? $requestTime : time();
            $days = (int) $request->query->get('days', 30);
            $days = max(7, min($days, 365));
            return $this->json($this->adminSession->getVisitsForDays($endDate, $days));
        }

        return $this->json(['error' => 'User tracking is disabled.'], 400);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'dashboard/topten', name: 'admin.api.dashboard.topten', methods: ['GET'])]
    public function topTen(): JsonResponse
    {
        $this->userIsAuthenticated();

        if ($this->configuration->get(item: 'main.enableUserTracking')) {
            $faqStatistics = new Faq\Statistics($this->configuration);
            return $this->json($faqStatistics->getTopTenData());
        }

        return $this->json(['error' => 'User tracking is disabled.'], 400);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'dashboard/news', name: 'admin.api.dashboard.news', methods: ['GET'])]
    public function news(): JsonResponse
    {
        $this->userIsAuthenticated();

        if (!$this->configuration->get(item: 'main.enableRecentNews')) {
            return $this->json(['error' => 'Recent news is disabled.'], Response::HTTP_FORBIDDEN);
        }

        $cacheKey = 'dashboard.news';

        $fresh = $this->getFreshCache($cacheKey);
        if ($fresh !== null) {
            return $this->json($fresh);
        }

        try {
            $httpClient = HttpClient::create(['max_redirects' => 2, 'timeout' => 10]);
            $response = $httpClient->request('GET', 'https://www.phpmyfaq.de/api/news/recent');

            if ($response->getStatusCode() === Response::HTTP_OK) {
                $data = $response->toArray(throw: false);
                if (array_key_exists('news', $data) && is_array($data['news'])) {
                    $data['news'] = array_slice($data['news'], offset: 0, length: 5);
                }

                $this->storeCache($cacheKey, $data);

                return $this->json($data);
            }

            $stale = $this->getStaleCache($cacheKey);
            if ($stale !== null) {
                return $this->json($stale);
            }

            return $this->json(['error' => 'Failed to fetch news.'], Response::HTTP_BAD_GATEWAY);
        } catch (TransportExceptionInterface $exception) {
            $stale = $this->getStaleCache($cacheKey);
            if ($stale !== null) {
                return $this->json($stale);
            }

            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_GATEWAY);
        }
    }

    /**
     * Returns the most popular search terms of the last 30 days.
     *
     * @throws Exception
     */
    #[Route(path: 'dashboard/searches', name: 'admin.api.dashboard.searches', methods: ['GET'])]
    public function searches(): JsonResponse
    {
        $this->userIsAuthenticated();

        $search = new Search($this->configuration);

        return $this->json($search->getMostPopularSearches(numResults: 7, withLang: false, timeWindow: 30));
    }

    /**
     * Returns aggregated content health counters (orphaned and stale FAQs).
     *
     * @throws Exception
     */
    #[Route(path: 'dashboard/content-health', name: 'admin.api.dashboard.content-health', methods: ['GET'])]
    public function contentHealth(): JsonResponse
    {
        $this->userIsAuthenticated();

        $faq = new AdminFaq($this->configuration);

        return $this->json($faq->getContentHealthStatistics());
    }

    /**
     * Returns the stored dashboard widget layout of the current admin user.
     *
     * @throws Exception
     */
    #[Route(path: 'dashboard/layout', name: 'admin.api.dashboard.layout.get', methods: ['GET'])]
    public function getLayout(): JsonResponse
    {
        $this->userIsAuthenticated();

        $dashboardLayout = new DashboardLayout($this->configuration);

        return $this->json(['config' => $dashboardLayout->get($this->currentUser->getUserId())]);
    }

    /**
     * Persists the dashboard widget layout of the current admin user.
     *
     * @throws Exception
     */
    #[Route(path: 'dashboard/layout', name: 'admin.api.dashboard.layout.save', methods: ['POST'])]
    public function saveLayout(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $data = json_decode($request->getContent());
        if (!is_object($data)) {
            return $this->json(['error' => 'Invalid request body.'], Response::HTTP_BAD_REQUEST);
        }

        $csrfToken = is_string($data->csrfToken ?? null) ? $data->csrfToken : null;
        if (!Token::getInstance($this->session)->verifyToken('dashboard', $csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $dashboardLayout = new DashboardLayout($this->configuration);
        $config = $this->sanitizeLayout($data->config ?? null);
        $saved = $dashboardLayout->save($this->currentUser->getUserId(), $config);

        if (!$saved) {
            return $this->json(['error' => 'Could not save layout.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['success' => true, 'config' => $config]);
    }

    /**
     * Removes the stored layout of the current admin user, reverting to the default.
     *
     * @throws Exception
     */
    #[Route(path: 'dashboard/layout/reset', name: 'admin.api.dashboard.layout.reset', methods: ['POST'])]
    public function resetLayout(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $data = json_decode($request->getContent());
        if (!is_object($data)) {
            return $this->json(['error' => 'Invalid request body.'], Response::HTTP_BAD_REQUEST);
        }

        $csrfToken = is_string($data->csrfToken ?? null) ? $data->csrfToken : null;
        if (!Token::getInstance($this->session)->verifyToken('dashboard', $csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $dashboardLayout = new DashboardLayout($this->configuration);
        if (!$dashboardLayout->reset($this->currentUser->getUserId())) {
            return $this->json(['error' => 'Could not reset layout.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['success' => true]);
    }

    /**
     * Validates an untrusted layout payload, keeping only known widgets and a clean shape.
     *
     * @return array<int, array{key: string, position: int, visible: bool}>
     */
    private function sanitizeLayout(mixed $config): array
    {
        if (!is_array($config)) {
            return [];
        }

        $clean = [];
        $seen = [];
        $position = 0;

        foreach ($config as $entry) {
            $key = is_object($entry) ? $entry->key ?? null : null;
            if (
                !is_string($key)
                || !in_array($key, self::ALLOWED_WIDGETS, strict: true)
                || array_key_exists($key, $seen)
            ) {
                continue;
            }

            $seen[$key] = true;
            $visible = is_object($entry) ? $entry->visible ?? true : true;
            $clean[] = [
                'key' => $key,
                'position' => $position++,
                'visible' => (bool) $visible,
            ];
        }

        return $clean;
    }
}
