<?php

/**
 * The Admin Comment Controller
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
 * @since     2023-10-25
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Comments;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommentController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route('admin/api/content/comments')]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::COMMENT_DELETE);

        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('delete-comment', $data->data->{'pmf-csrf-token'})) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        $comments = new Comments(Configuration::getConfigurationInstance());
        $commentIds = $data->data->{'comments[]'} ?? [];

        $result = false;
        if (!is_null($commentIds)) {
            if (!is_array($commentIds)) {
                $commentIds = [$commentIds];
            }

            foreach ($commentIds as $commentId) {
                $result = $comments->delete($data->type, $commentId);
            }

            return $this->json(['success' => $result], Response::HTTP_OK);
        } else {
            return $this->json(['error' => false], Response::HTTP_BAD_REQUEST);
        }
    }
}
