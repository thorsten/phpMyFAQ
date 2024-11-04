<?php

/**
 * The Admin Question Controller
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
 * @since     2023-10-30
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Question;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuestionController extends AbstractController
{
    /**
     * @throws \Exception
     */
    #[Route('admin/api/question/delete')]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::QUESTION_DELETE);

        $data = json_decode($request->getContent());

        if (
            !Token::getInstance($this->container->get('session'))
                ->verifyToken('delete-questions', $data->data->{'pmf-csrf-token'})
        ) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        $questionIds = $data->data->{'questions[]'};
        $question = new Question($this->configuration);

        if (!is_null($questionIds)) {
            if (!is_array($questionIds)) {
                $questionIds = [$questionIds];
            }

            foreach ($questionIds as $questionId) {
                $question->delete((int)$questionId);
            }

            return $this->json(['success' => Translation::get('ad_open_question_deleted')], Response::HTTP_OK);
        } else {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }
    }
}
