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
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-15
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Administration\Api;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Faq;
use phpMyFAQ\Session;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DashboardController extends AbstractController
{
    #[Route('admin/api/dashboard/versions')]
    public function versions(): JsonResponse
    {
        $this->userIsAuthenticated();

        $api = new Api(Configuration::getConfigurationInstance());
        try {
            $versions = $api->getVersions();
            if (version_compare($versions['installed'], $versions['stable']) < 0) {
                $info = ['success' => Translation::get('ad_you_should_update')];
            } else {
                $info = ['success' => Translation::get('ad_xmlrpc_latest') . ': phpMyFAQ ' . $versions['stable']];
            }

            return $this->json($info);
        } catch (DecodingExceptionInterface | TransportExceptionInterface | Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @throws \Exception
     */
    #[Route('admin/api/dashboard/visits')]
    public function visits(): JsonResponse
    {
        $this->userIsAuthenticated();

        $configuration = Configuration::getConfigurationInstance();

        if ($configuration->get('main.enableUserTracking')) {
            $session = new Session($configuration);
            return $this->json($session->getLast30DaysVisits());
        }

        return $this->json(['error' => 'User tracking is disabled.'], 400);
    }

    #[Route('admin/api/dashboard/topten')]
    public function topTen(): JsonResponse
    {
        $this->userIsAuthenticated();

        $configuration = Configuration::getConfigurationInstance();

        if ($configuration->get('main.enableUserTracking')) {
            $faq = new Faq($configuration);
            return $this->json($faq->getTopTenData());
        }

        return $this->json(['error' => 'User tracking is disabled.'], 400);
    }
}
