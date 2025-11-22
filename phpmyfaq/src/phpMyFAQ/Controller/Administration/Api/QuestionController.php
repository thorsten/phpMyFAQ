<?php

declare(strict_types=1);

/**
 * The Admin Question Controller
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
 * @since     2023-10-30
 */

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
use Symfony\Component\Routing\Annotation\Route;

final class QuestionController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route('admin/api/question/delete')]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::QUESTION_DELETE);

        $data = json_decode($request->getContent());

        if (!Token::getInstance($this->container->get(id: 'session'))->verifyToken(
            'delete-questions',
            $data->data->{'pmf-csrf-token'},
        )) {
            return $this->json(['error' => Translation::get(
                languageKey: 'msgNoPermission',
            )], Response::HTTP_UNAUTHORIZED);
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

            return $this->json(['success' => Translation::get(
                languageKey: 'ad_open_question_deleted',
            )], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get(languageKey: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
    }

    #[Route('admin/api/question/toggle')]
    public function toggle(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::QUESTION_ADD);

        $session = $this->container->get(id: 'session');
        $question = $this->container->get(id: 'phpmyfaq.question');

        $data = json_decode($request->getContent());

        if (!Token::getInstance($session)->verifyToken('toggle-question-visibility', $data->csrfToken)) {
            return $this->json(['error' => Translation::get(
                languageKey: 'msgNoPermission',
            )], Response::HTTP_UNAUTHORIZED);
        }

        $questionId = $data->questionId;

        if (!is_null($questionId)) {
            $isVisible = $question->getVisibility($questionId);
            $question->setVisibility($questionId, $isVisible === 'N' ? 'Y' : 'N');
            $translation = $isVisible === 'N'
                ? Translation::get(languageKey: 'ad_gen_yes')
                : Translation::get(languageKey: 'ad_gen_no');
            return $this->json(['success' => $translation], Response::HTTP_OK);
        }

        return $this->json(['error' => 'toggle not successful'], Response::HTTP_BAD_REQUEST);
    }
}
