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
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-15
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Administration\Api;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Session;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DashboardController extends AbstractController
{
    #[Route('admin/api/dashboard/versions')]
    public function versions(): JsonResponse
    {
        $this->userIsAuthenticated();

        $response = new JsonResponse();
        $faqConfig = Configuration::getConfigurationInstance();

        $api = new Api($faqConfig);
        try {
            $versions = $api->getVersions();
            $response->setStatusCode(Response::HTTP_OK);
            if (version_compare($versions['installed'], $versions['stable']) < 0) {
                $response->setData(
                    ['success' => Translation::get('ad_you_should_update')]
                );
            } else {
                $response->setData(
                    ['success' => Translation::get('ad_xmlrpc_latest') . ': phpMyFAQ ' . $versions['stable']]
                );
            }
        } catch (DecodingExceptionInterface | TransportExceptionInterface | Exception $e) {
            $response->setStatusCode(Response::HTTP_BAD_GATEWAY);
            $response->setData(['error' => $e->getMessage()]);
        }
        return $response;
    }

    #[Route('admin/api/dashboard/visits')]
    public function visits(): JsonResponse
    {
        $this->userIsAuthenticated();

        $response = new JsonResponse();
        $faqConfig = Configuration::getConfigurationInstance();

        if ($faqConfig->get('main.enableUserTracking')) {
            $session = new Session($faqConfig);
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData($session->getLast30DaysVisits());

            return $response;
        }

        return new JsonResponse(['error' => 'User tracking is disabled.'], Response::HTTP_BAD_REQUEST);
    }
}
