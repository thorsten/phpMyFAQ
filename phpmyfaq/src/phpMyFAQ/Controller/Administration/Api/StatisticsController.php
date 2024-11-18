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
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-04-21
 */

namespace phpMyFAQ\Controller\Administration\Api;

use JsonException;
use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Search;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatisticsController extends AbstractController
{
    /**
     * @throws Exception|JsonException
     */
    #[Route('./admin/api/statistics/admin-log', methods: ['DELETE'])]
    public function deleteAdminLog(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::STATISTICS_VIEWLOGS);

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        if (!Token::getInstance($this->container->get('session'))->verifyToken('delete-adminlog', $data->csrfToken)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        $logging = new AdminLog($this->configuration);
        if ($logging->delete()) {
            return $this->json(['success' => Translation::get('ad_adminlog_delete_success')], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get('ad_adminlog_delete_failure')], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws Exception|JsonException
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
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        $search = new Search($this->configuration);
        if ($search->deleteAllSearchTerms()) {
            return $this->json(['success' => Translation::get('ad_searchterm_del_suc')], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get('ad_searchterm_del_err')], Response::HTTP_BAD_REQUEST);
    }
}
