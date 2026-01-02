<?php

/**
 * FAQ Controller
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
 * @since     2002-09-16
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Faq\FaqService;
use phpMyFAQ\Filter;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\TwigFilter;

final class FaqController extends AbstractFrontController
{
    /**
     * Displays the form to add a new FAQ
     *
     * @throws \Exception
     */
    #[Route(path: '/add-faq.html', name: 'public.faq.add', methods: ['GET'])]
    public function add(Request $request): Response
    {
        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);
        $faqSession->userTracking('new_entry', 0);

        // Get current groups
        $currentGroups = $this->currentUser->perm->getUserGroups($this->currentUser->getUserId());

        $faqService = new FaqService($this->configuration, $this->currentUser, $currentGroups);

        if (!$faqService->canUserAddFaq()) {
            if ($this->currentUser->getUserId() === -1) {
                return new RedirectResponse($this->configuration->getDefaultUrl() . 'login');
            }

            return new RedirectResponse($this->configuration->getDefaultUrl());
        }

        $selectedQuestion = Filter::filterVar($request->query->get('question'), FILTER_VALIDATE_INT);
        $selectedCategory = Filter::filterVar($request->query->get('cat'), FILTER_VALIDATE_INT, -1);

        $faqData = $faqService->prepareAddFaqData($selectedQuestion, $selectedCategory);

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
            'title' => sprintf('%s - %s', Translation::get(key: 'msgAddContent'), $this->configuration->getTitle()),
            'metaDescription' => sprintf(
                '%s | %s',
                Translation::get(key: 'msgNewContentHeader'),
                $this->configuration->getTitle(),
            ),
            'msgNewContentHeader' => Translation::get(key: 'msgNewContentHeader'),
            'msgNewContentAddon' => Translation::get(key: 'msgNewContentAddon'),
            'lang' => $this->configuration->getLanguage()->getLanguage(),
            'openQuestionID' => $faqData['selectedQuestion'],
            'defaultContentMail' => $faqService->getDefaultUserEmail(),
            'defaultContentName' => $faqService->getDefaultUserName(),
            'msgNewContentName' => Translation::get(key: 'msgNewContentName'),
            'msgNewContentMail' => Translation::get(key: 'msgNewContentMail'),
            'msgNewContentCategory' => Translation::get(key: 'msgNewContentCategory'),
            'selectedCategory' => $faqData['selectedCategory'],
            'categories' => $faqData['categories'],
            'msgNewContentTheme' => Translation::get(key: 'msgNewContentTheme'),
            'readonly' => $faqData['readonly'],
            'question' => $faqData['question'],
            'msgNewContentArticle' => Translation::get(key: 'msgNewContentArticle'),
            'msgNewContentKeywords' => Translation::get(key: 'msgNewContentKeywords'),
            'msgNewContentLink' => Translation::get(key: 'msgNewContentLink'),
            'captchaFieldset' => $captchaHelper->renderCaptcha(
                $captcha,
                'add',
                Translation::get(key: 'msgCaptcha'),
                $this->currentUser->isLoggedIn(),
            ),
            'msgNewContentSubmit' => Translation::get(key: 'msgNewContentSubmit'),
            'enableWysiwygEditor' => $this->configuration->get('main.enableWysiwygEditorFrontend'),
            'currentTimestamp' => $request->server->get('REQUEST_TIME'),
            'msgSeparateKeywordsWithCommas' => Translation::get(key: 'msgSeparateKeywordsWithCommas'),
            'noCategories' => $faqData['noCategories'],
            'msgFormDisabledDueToMissingCategories' => Translation::get(key: 'msgFormDisabledDueToMissingCategories'),
            'displayFullForm' => $faqData['displayFullForm'],
        ];

        // Collect data for displaying form
        foreach ($faqData['formData'] as $input) {
            $active = sprintf('id%d_active', (int) $input->input_id);
            $label = sprintf('id%d_label', (int) $input->input_id);
            $required = sprintf('id%d_required', (int) $input->input_id);
            $templateVars[$active] = (bool) $input->input_active;
            $templateVars[$label] = $input->input_label;
            $templateVars[$required] = (int) $input->input_required !== 0 ? 'required' : '';
        }

        return $this->render('add.twig', $templateVars);
    }
}
