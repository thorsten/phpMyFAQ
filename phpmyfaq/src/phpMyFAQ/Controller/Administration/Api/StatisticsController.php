<?php

/**
 * The Statistics Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-04-21
 */

namespace phpMyFAQ\Controller\Administration\Api;

use JsonException;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StatisticsController extends AbstractController
{
    /**
     * @throws Exception|JsonException
     * @throws \Exception
     */
    #[Route('./admin/api/statistics/admin-log', methods: ['DELETE'])]
    public function deleteAdminLog(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::STATISTICS_VIEWLOGS);

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        if (!Token::getInstance($this->container->get('session'))->verifyToken('delete-adminlog', $data->csrfToken)) {
            return $this->json(['error' => Translation::get('msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        if ($this->container->get('phpmyfaq.admin.admin-log')->delete()) {
            return $this->json(['success' => Translation::get('ad_adminlog_delete_success')], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get('ad_adminlog_delete_failure')], Response::HTTP_BAD_REQUEST);
    }


    /**
     * @throws Exception|JsonException
     * @throws \Exception
     */
    #[Route('./admin/api/statistics/sessions', name: 'admin.api.statistics.sessions.truncate', methods: ['DELETE'])]
    public function truncateSessions(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::STATISTICS_VIEWLOGS);

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);
        if (
            !Token::getInstance($this->container->get('session'))
                ->verifyToken('sessions', $data->csrfToken)
        ) {
            return $this->json(['error' => Translation::get('msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $month = Filter::filterVar($request->get('month'), FILTER_SANITIZE_SPECIAL_CHARS);
        if ($this->container->get('phpmyfaq.helper.statistics')->deleteTrackingFiles($month)) {
            return $this->json(['success' => Translation::get('ad_adminlog_delete_success')], Response::HTTP_OK);
        }

        return $this->json(['error' => 'Cannot delete sessions.'], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws Exception|JsonException
     * @throws \Exception
     */
    #[Route('./admin/api/statistics/search-terms', methods: ['DELETE'])]
    public function truncateSearchTerms(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::STATISTICS_VIEWLOGS);

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        if (
            !Token::getInstance($this->container->get('session'))
                ->verifyToken('truncate-search-terms', $data->csrfToken)
        ) {
            return $this->json(['error' => Translation::get('msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        if ($this->container->get('phpmyfaq.search')->deleteAllSearchTerms()) {
            return $this->json(['success' => Translation::get('ad_searchterm_del_suc')], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get('ad_searchterm_del_err')], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws \Exception
     */
    #[Route('./admin/api/statistics/ratings/clear', name: 'admin.api.statistics.ratings.clear', methods: ['DELETE'])]
    public function clearRatings(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::STATISTICS_VIEWLOGS);

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        if (!Token::getInstance($this->container->get('session'))->verifyToken('clear-statistics', $data->csrfToken)) {
            return $this->json(['error' => Translation::get('msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        if ($this->container->get('phpmyfaq.rating')->deleteAll()) {
            return $this->json(['success' => Translation::get('msgDeleteAllVotings')], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get('msgDeleteAllVotings')], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws \Exception
     */
    #[Route('./admin/api/statistics/visits/clear', name: 'admin.api.statistics.visits.clear', methods: ['DELETE'])]
    public function clearVisits(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::STATISTICS_VIEWLOGS);

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        if (!Token::getInstance($this->container->get('session'))->verifyToken('clear-visits', $data->csrfToken)) {
            return $this->json(['error' => Translation::get('msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        if ($this->container->get('phpmyfaq.helper.statistics')->clearAllVisits()) {
            return $this->json(['success' => Translation::get('ad_reset_visits_success')], Response::HTTP_OK);
        }

        return $this->json(['error' => 'Cannot clear visits.'], Response::HTTP_BAD_REQUEST);
    }
}
