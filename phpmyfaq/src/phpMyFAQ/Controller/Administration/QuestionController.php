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
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-30
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller;
use phpMyFAQ\Question;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuestionController extends Controller
{
    #[Route('admin/api/question/delete')]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission('delquestion');

        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('delete-questions', $data->data->{'pmf-csrf-token'})) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            return $response;
        }

        $questionIds = $data->data->{'questions[]'};
        $question = new Question($configuration);

        if (!is_null($questionIds)) {
            if (!is_array($questionIds)) {
                $questionIds = [$questionIds];
            }
            foreach ($questionIds as $questionId) {
                $question->deleteQuestion((int)$questionId);
            }

            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(['success' => Translation::get('ad_open_question_deleted')]);
        } else {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
        }

        return $response;
    }
}
