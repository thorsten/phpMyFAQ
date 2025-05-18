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
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-09-17
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Bookmark;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookmarkController extends AbstractController
{
    /**
     * @throws \JsonException
     * @throws \Exception
     */
    #[Route('api/bookmark/create')]
    public function create(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);
        $bookmarkId = Filter::filterVar($data->id, FILTER_VALIDATE_INT);
        $csrfToken = Filter::filterVar($data->csrfToken, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance($this->container->get('session'))->verifyToken('add-bookmark', $csrfToken)) {
            return $this->json(['error' => Translation::get('ad_msg_noauth')], Response::HTTP_UNAUTHORIZED);
        }

        $bookmark = new Bookmark($this->configuration, $this->currentUser);

        if ($bookmark->add($bookmarkId)) {
            return $this->json([
                'success' => Translation::get('msgBookmarkAdded'),
                'linkText' => Translation::get('removeBookmark'),
                'csrfToken' => Token::getInstance($this->container->get('session'))->getTokenString('delete-bookmark')
            ], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get('msgError')], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws \JsonException
     * @throws \Exception
     */
    #[Route('api/bookmark/delete')]
    public function delete(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);
        $bookmarkId = Filter::filterVar($data->id, FILTER_VALIDATE_INT);
        $csrfToken = Filter::filterVar($data->csrfToken, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance($this->container->get('session'))->verifyToken('delete-bookmark', $csrfToken)) {
            return $this->json(['error' => Translation::get('ad_msg_noauth')], Response::HTTP_UNAUTHORIZED);
        }

        $bookmark = new Bookmark($this->configuration, $this->currentUser);

        if ($bookmark->remove($bookmarkId)) {
            return $this->json([
                'success' => Translation::get('msgBookmarkRemoved'),
                'linkText' => Translation::get('msgAddBookmark'),
                'csrfToken' => Token::getInstance($this->container->get('session'))->getTokenString('add-bookmark')
            ], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get('msgError')], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws \JsonException
     * @throws \Exception
     */
    #[Route('api/bookmark/delete-all')]
    public function deleteAll(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);
        $csrfToken = Filter::filterVar($data->csrfToken, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance($this->container->get('session'))->verifyToken('delete-all-bookmarks', $csrfToken)) {
            return $this->json(['error' => Translation::get('ad_msg_noauth')], Response::HTTP_UNAUTHORIZED);
        }

        $bookmark = new Bookmark($this->configuration, $this->currentUser);

        if ($bookmark->removeAll()) {
            return $this->json(['success' => Translation::get('msgBookmarkRemoved')], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get('msgError')], Response::HTTP_BAD_REQUEST);
    }
}
