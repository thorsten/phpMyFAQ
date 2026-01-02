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
use phpMyFAQ\Faq\QuestionService;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\QuestionHelper;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\TwigFilter;

final class QuestionsController extends AbstractFrontController
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

    /**
     * Displays the form to ask a new question
     *
     * @throws \Exception
     */
    #[Route(path: '/ask.html', name: 'public.question.ask', methods: ['GET'])]
    public function ask(Request $request): Response
    {
        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);
        $faqSession->userTracking('ask_question', 0);

        // Get current groups
        $currentGroups = $this->currentUser->perm->getUserGroups($this->currentUser->getUserId());

        $questionService = new QuestionService($this->configuration, $this->currentUser, $currentGroups);

        if (!$questionService->canUserAskQuestion()) {
            return new RedirectResponse($this->configuration->getDefaultUrl() . 'login');
        }

        $categoryId = Filter::filterVar($request->query->get('category_id'), FILTER_VALIDATE_INT, 0);

        $questionData = $questionService->prepareAskQuestionData($categoryId);

        $captcha = $this->container->get('phpmyfaq.captcha');
        $captchaHelper = $this->container->get('phpmyfaq.captcha.helper.captcha_helper');

        // Add Twig filter
        $this->addFilter(new TwigFilter('repeat', static fn($string, $times): string => str_repeat(
            (string) $string,
            $times,
        )));

        // Prepare template variables
        $templateVars = [
            ...$this->getHeader($request),
            'title' => sprintf('%s - %s', Translation::get(key: 'msgQuestion'), $this->configuration->getTitle()),
            'metaDescription' => sprintf(
                Translation::get(key: 'msgQuestionMetaDesc'),
                $this->configuration->getTitle(),
            ),
            'msgMatchingQuestions' => Translation::get(key: 'msgMatchingQuestions'),
            'msgFinishSubmission' => Translation::get(key: 'msgFinishSubmission'),
            'lang' => $this->configuration->getLanguage()->getLanguage(),
            'defaultContentMail' => $questionService->getDefaultUserEmail(),
            'defaultContentName' => $questionService->getDefaultUserName(),
            'selectedCategory' => $questionData['selectedCategory'],
            'categories' => $questionData['categories'],
            'captchaFieldset' => $captchaHelper->renderCaptcha(
                $captcha,
                'ask',
                Translation::get(key: 'msgCaptcha'),
                $this->currentUser->isLoggedIn(),
            ),
            'msgNewContentSubmit' => Translation::get(key: 'msgNewContentSubmit'),
            'noCategories' => $questionData['noCategories'],
            'msgFormDisabledDueToMissingCategories' => Translation::get(key: 'msgFormDisabledDueToMissingCategories'),
        ];

        // Collect data for displaying form
        foreach ($questionData['formData'] as $input) {
            if ((int) $input->input_active !== 0) {
                $label = sprintf('id%d_label', (int) $input->input_id);
                $required = sprintf('id%d_required', (int) $input->input_id);
                $templateVars[$label] = $input->input_label;
                $templateVars[$required] = (int) $input->input_required !== 0 ? 'required' : '';
            }
        }

        return $this->render('ask.twig', $templateVars);
    }
}
