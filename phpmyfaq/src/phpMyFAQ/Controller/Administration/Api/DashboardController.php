<?php

declare(strict_types=1);

/**
 * The Admin Dashboard Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-15
 */

namespace phpMyFAQ\Controller\Administration\Api;

use Exception;
use JsonException;
use phpMyFAQ\Administration\Api;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Faq;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class DashboardController extends AbstractController
{
    /**
     * @throws JsonException
     */
    #[Route('admin/api/dashboard/verify', name: 'admin.api.dashboard.verify', methods: ['POST'])]
    public function verify(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $data = $request->getContent();
        $api = new Api($this->configuration, new System());

        return $this->json($api->setRemoteHashes($data)->getVerificationIssues());
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/dashboard/versions', name: 'admin.api.dashboard.versions', methods: ['GET'])]
    public function versions(): JsonResponse
    {
        $this->userIsAuthenticated();

        $api = new Api($this->configuration, new System());
        $releaseEnvironment = $this->configuration->get(item: 'upgrade.releaseEnvironment');

        try {
            $versions = $api->getVersions();
            if (version_compare($versions['installed'], $versions[$releaseEnvironment]) < 0) {
                $info = ['warning' => Translation::get(languageKey: 'ad_you_should_update')];
            } else {
                $info = [
                    'success' =>
                        Translation::get(languageKey: 'ad_xmlrpc_latest') . ': phpMyFAQ ' . $versions['stable'],
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
    #[Route('admin/api/dashboard/visits', name: 'admin.api.dashboard.visits', methods: ['GET'])]
    public function visits(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        if ($this->configuration->get(item: 'main.enableUserTracking')) {
            $session = $this->container->get('phpmyfaq.admin.session');
            $endDate = $request->server->get('REQUEST_TIME');
            return $this->json($session->getLast30DaysVisits($endDate));
        }

        return $this->json(['error' => 'User tracking is disabled.'], 400);
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/dashboard/topten', name: 'admin.api.dashboard.topten', methods: ['GET'])]
    public function topTen(): JsonResponse
    {
        $this->userIsAuthenticated();

        if ($this->configuration->get(item: 'main.enableUserTracking')) {
            $faqStatistics = new Faq\Statistics($this->configuration);
            return $this->json($faqStatistics->getTopTenData());
        }

        return $this->json(['error' => 'User tracking is disabled.'], 400);
    }
}
