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
use phpMyFAQ\Administration\RemoteApiClient;
use phpMyFAQ\Administration\Session as AdminSession;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Faq;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly AdminSession $adminSession,
    ) {
        parent::__construct();
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

        $api = new RemoteApiClient($this->configuration, new System());
        $releaseEnvironment = $this->configuration->get(item: 'upgrade.releaseEnvironment');

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
                        Translation::get(key: 'ad_xmlrpc_latest') . ': phpMyFAQ ' . ($versions['stable'] ?? ''),
                ];
            }

            return $this->json($info);
        } catch (DecodingExceptionInterface|TransportExceptionInterface|Exception $exception) {
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
            $endDate = $request->server->get('REQUEST_TIME');
            return $this->json($this->adminSession->getLast30DaysVisits($endDate));
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

        try {
            $httpClient = HttpClient::create(['max_redirects' => 2, 'timeout' => 10]);
            $response = $httpClient->request('GET', 'https://www.phpmyfaq.de/api/news/recent');

            if ($response->getStatusCode() === Response::HTTP_OK) {
                $data = $response->toArray(throw: false);
                if (array_key_exists('news', $data) && is_array($data['news'])) {
                    $data['news'] = array_slice($data['news'], offset: 0, length: 5);
                }

                return $this->json($data);
            }

            return $this->json(['error' => 'Failed to fetch news.'], Response::HTTP_BAD_GATEWAY);
        } catch (TransportExceptionInterface $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_GATEWAY);
        }
    }
}
