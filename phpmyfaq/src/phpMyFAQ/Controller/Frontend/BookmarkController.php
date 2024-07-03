<?php

/**
 * The Bookmark Controller
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
 * @since     2023-09-17
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Bookmark;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class BookmarkController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route('api/bookmark/remove')]
    public function remove(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $id = Filter::filterVar($request->get('bookmarkId'), FILTER_VALIDATE_INT);

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $bookmark = new Bookmark($this->configuration, $currentUser);

        if ($bookmark->remove($id)) {
            return $this->json([
                'success' => Translation::get('msgBookmarkRemoved'),
                'linkText' => Translation::get('msgAddBookmark')
            ], JsonResponse::HTTP_OK);
        } else {
            return $this->json(['error' => Translation::get('msgError')], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws Exception
     */
    #[Route('api/bookmark/add')]
    public function add(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $id = Filter::filterVar($request->get('bookmarkId'), FILTER_VALIDATE_INT);

        $currentUser = CurrentUser::getCurrentUser($this->configuration);

        $bookmark = new Bookmark($this->configuration, $currentUser);

        if ($bookmark->add($id)) {
            return $this->json([
                'success' => Translation::get('msgBookmarkAdded'),
                'linkText' => Translation::get('removeBookmark')
            ], JsonResponse::HTTP_OK);
        } else {
            return $this->json(['error' => Translation::get('msgError')], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}
