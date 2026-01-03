<?php

/**
 * The Admin Tag Controller
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
 * @since     2023-10-27
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\Tag;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class TagController extends AbstractController
{
    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: 'admin/api/content/tag')]
    public function update(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $postData = json_decode($request->getContent());

        if (!Token::getInstance($this->session)->verifyToken('tags', $postData->csrf)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $id = Filter::filterVar($postData->id, FILTER_VALIDATE_INT);
        $newTag = Filter::filterVar($postData->tag, FILTER_SANITIZE_SPECIAL_CHARS);

        $tag = new Tag();
        $tag->setId($id);
        $tag->setName($newTag);

        if ($this->container->get(id: 'phpmyfaq.tags')->update($tag)) {
            return $this->json(['updated' => Translation::get(key: 'ad_entryins_suc')], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get(key: 'msgErrorOccurred')], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws Exception|\Exception
     */
    #[Route(path: 'admin/api/content/tags')]
    public function search(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $tag = $this->container->get(id: 'phpmyfaq.tags');

        $autoCompleteValue = Filter::filterVar($request->query->get('search'), FILTER_SANITIZE_SPECIAL_CHARS);

        if (!is_null($autoCompleteValue)) {
            if (strpos((string) $autoCompleteValue, ',')) {
                $arrayOfValues = explode(',', (string) $autoCompleteValue);
                $autoCompleteValue = end($arrayOfValues);
            }

            $tags = $tag->getAllTags(
                strtolower(trim((string) $autoCompleteValue)),
                PMF_TAGS_CLOUD_RESULT_SET_SIZE,
                true,
            );
        } else {
            $tags = $tag->getAllTags();
        }

        if ($this->currentUser->perm->hasPermission($this->currentUser->getUserId(), PermissionType::FAQ_EDIT)) {
            $numTags = 0;
            $tagNames = [];
            foreach ($tags as $tag) {
                ++$numTags;
                if ($numTags <= PMF_TAGS_AUTOCOMPLETE_RESULT_SET_SIZE) {
                    $currentTag = new stdClass();
                    $currentTag->tagName = $tag;
                    $tagNames[] = $currentTag;
                }
            }

            return $this->json($tagNames, Response::HTTP_OK);
        }

        return $this->json([], Response::HTTP_OK);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: 'admin/api/content/tag/:tagId')]
    public function delete(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $tagId = (int) Filter::filterVar($request->attributes->get('tagId'), FILTER_VALIDATE_INT);

        if ($this->container->get(id: 'phpmyfaq.tags')->delete($tagId)) {
            return $this->json(['success' => Translation::get(key: 'ad_tag_delete_success')], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get(key: 'ad_tag_delete_error')], Response::HTTP_BAD_REQUEST);
    }
}
