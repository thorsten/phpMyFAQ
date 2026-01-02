<?php

/**
 * Open Questions Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-09-17
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Category;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Helper\QuestionHelper;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OpenQuestionsController extends AbstractFrontController
{
    /**
     * Displays the open questions page.
     *
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/open-questions.html', name: 'public.open-questions')]
    public function index(Request $request): Response
    {
        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);
        $faqSession->userTracking('open_questions', 0);

        $category = new Category($this->configuration);
        $questionHelper = new QuestionHelper();
        $questionHelper->setConfiguration($this->configuration)->setCategory($category);

        return $this->render('open-questions.twig', [
            ...$this->getHeader($request),
            'title' => sprintf('%s - %s', Translation::get(key: 'msgOpenQuestions'), $this->configuration->getTitle()),
            'metaDescription' => sprintf(
                Translation::get(key: 'msgOpenQuestionsMetaDesc'),
                $this->configuration->getTitle(),
            ),
            'pageHeader' => Translation::get(key: 'msgOpenQuestions'),
            'msgQuestionText' => Translation::get(key: 'msgQuestionText'),
            'msgDate_User' => Translation::get(key: 'msgDate_User'),
            'msgQuestion2' => Translation::get(key: 'msgQuestion2'),
            'openQuestions' => $questionHelper->getOpenQuestions(),
            'isCloseQuestionEnabled' => $this->configuration->get('records.enableCloseQuestion'),
            'userHasPermissionToAnswer' => $this->currentUser->perm->hasPermission(
                $this->currentUser->getUserId(),
                PermissionType::FAQ_ADD->value,
            ),
            'msgQuestionsWaiting' => Translation::get(key: 'msgQuestionsWaiting'),
            'msgNoQuestionsAvailable' => Translation::get(key: 'msgNoQuestionsAvailable'),
            'msg2answerFAQ' => Translation::get(key: 'msg2answerFAQ'),
            'msg2answer' => Translation::get(key: 'msg2answer'),
        ]);
    }
}
