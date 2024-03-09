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
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-27
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\TagEntity;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Tags;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TagController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route('admin/api/content/tag')]
    public function update(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $configuration = Configuration::getConfigurationInstance();
        $tags = new Tags($configuration);

        $postData = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('tags', $postData->csrf)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        $id = Filter::filterVar($postData->id, FILTER_VALIDATE_INT);
        $newTag = Filter::filterVar($postData->tag, FILTER_SANITIZE_SPECIAL_CHARS);

        $tagEntity = new TagEntity();
        $tagEntity->setId($id);
        $tagEntity->setName($newTag);

        if ($tags->updateTag($tagEntity)) {
            return $this->json(['updated' => Translation::get('ad_entryins_suc')], Response::HTTP_OK);
        } else {
            return $this->json(['error' => Translation::get('ad_entryins_fail')], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/content/tags')]
    public function search(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);
        $tag = new Tags($configuration);

        $autoCompleteValue = Filter::filterVar($request->query->get('search'), FILTER_SANITIZE_SPECIAL_CHARS);

        if (!is_null($autoCompleteValue)) {
            if (strpos((string) $autoCompleteValue, ',')) {
                $arrayOfValues = explode(',', (string) $autoCompleteValue);
                $autoCompleteValue = end($arrayOfValues);
            }

            $tags = $tag->getAllTags(
                strtolower(trim((string) $autoCompleteValue)),
                PMF_TAGS_CLOUD_RESULT_SET_SIZE,
                true
            );
        } else {
            $tags = $tag->getAllTags();
        }

        if ($user->perm->hasPermission($user->getUserId(), PermissionType::FAQ_EDIT)) {
            $i = 0;
            $tagNames = [];
            foreach ($tags as $tag) {
                ++$i;
                if ($i <= PMF_TAGS_AUTOCOMPLETE_RESULT_SET_SIZE) {
                    $currentTag = new stdClass();
                    $currentTag->tagName = $tag;
                    $tagNames[] = $currentTag;
                }
            }

            return $this->json($tagNames, Response::HTTP_OK);
        }

        return $this->json([], Response::HTTP_OK);
    }
}
