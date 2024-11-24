<?php

/**
 * The Admin Search Controller
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
 * @since     2023-10-26
 */

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Search;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route('admin/api/search/term')]
    public function deleteTerm(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::STATISTICS_VIEWLOGS);

        $deleteData = json_decode($request->getContent());

        $search = new Search($this->configuration);

        if (
            !Token::getInstance($this->container->get('session'))
                ->verifyToken('delete-searchterm', $deleteData->csrf)
        ) {
            return $this->json(['error' => Translation::get('msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $searchId = Filter::filterVar($deleteData->searchTermId, FILTER_VALIDATE_INT);

        if ($search->deleteSearchTermById($searchId)) {
            return $this->json(['deleted' => $searchId], Response::HTTP_OK);
        } else {
            return $this->json(['error' => $searchId], Response::HTTP_BAD_REQUEST);
        }
    }
}
