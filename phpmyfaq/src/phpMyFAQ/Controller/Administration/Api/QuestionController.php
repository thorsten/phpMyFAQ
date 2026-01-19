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
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-30
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use Exception;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Question;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class QuestionController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route(path: 'question/delete', name: 'admin.api.question.delete', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::QUESTION_DELETE);

        $data = json_decode($request->getContent());

        if (!Token::getInstance($this->session)->verifyToken('delete-questions', $data->data->{'pmf-csrf-token'})) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $questionIds = $data->data->{'questions[]'};
        $question = new Question($this->configuration);

        if (!is_null($questionIds)) {
            if (!is_array($questionIds)) {
                $questionIds = [$questionIds];
            }

            foreach ($questionIds as $questionId) {
                $question->delete((int) $questionId);
            }

            return $this->json(['success' => Translation::get(key: 'ad_open_question_deleted')], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
    }

    #[Route(path: 'question/toggle', name: 'admin.api.question.toggle', methods: ['PUT'])]
    public function toggle(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::QUESTION_ADD);

        $question = $this->container->get(id: 'phpmyfaq.question');

        $data = json_decode($request->getContent());

        if (!Token::getInstance($this->session)->verifyToken('toggle-question-visibility', $data->csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $questionId = (int) $data->questionId;

        if (!is_null($questionId)) {
            $isVisible = $question->getVisibility($questionId);
            $question->setVisibility($questionId, $isVisible === 'N' ? 'Y' : 'N');
            $translation = $isVisible === 'N'
                ? Translation::get(key: 'ad_gen_yes')
                : Translation::get(key: 'ad_gen_no');
            return $this->json(['success' => $translation], Response::HTTP_OK);
        }

        return $this->json(['error' => 'toggle not successful'], Response::HTTP_BAD_REQUEST);
    }
}
