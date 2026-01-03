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

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Date;
use phpMyFAQ\Entity\SeoEntity;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Enums\SeoType;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\FaqCreationService;
use phpMyFAQ\Faq\FaqDisplayService;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CommentHelper;
use phpMyFAQ\Link;
use phpMyFAQ\Seo;
use phpMyFAQ\Services;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\Visits;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\TwigFilter;

final class FaqController extends AbstractFrontController
{
    /**
     * Displays the form to add a new FAQ
     *
     * @throws Exception|LoaderError
     */
    #[Route(path: '/add-faq.html', name: 'public.faq.add', methods: ['GET'])]
    public function add(Request $request): Response
    {
        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);
        $faqSession->userTracking('new_entry', 0);

        // Get current groups
        $currentGroups = $this->currentUser->perm->getUserGroups($this->currentUser->getUserId());

        $faqService = new FaqCreationService($this->configuration, $this->currentUser, $currentGroups);

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

    /**
     * Displays a single FAQ article with comments, ratings, and related content
     *
     * @throws Exception
     */
    #[Route(path: '/faq/{categoryId}/{faqId}/{slug}.html', name: 'public.faq.show', methods: ['GET'])]
    public function show(Request $request): Response
    {
        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);

        // Get parameters
        $cat = Filter::filterVar($request->attributes->get('categoryId'), FILTER_VALIDATE_INT, 0);

        // Get faqId from route attributes (new routes) or query parameters (legacy/backward compatibility)
        $faqId = Filter::filterVar($request->attributes->get('faqId'), FILTER_VALIDATE_INT);
        if (!$faqId) {
            $faqId = Filter::filterVar($request->query->get('id'), FILTER_VALIDATE_INT, 0);
        }

        $solutionId = Filter::filterVar($request->query->get('solution_id'), FILTER_VALIDATE_INT);
        $highlight = Filter::filterVar($request->query->get('highlight'), FILTER_SANITIZE_SPECIAL_CHARS);
        $bookmarkAction = Filter::filterVar($request->query->get('bookmark_action'), FILTER_SANITIZE_SPECIAL_CHARS);

        // Initialize core objects
        $faq = new Faq($this->configuration);
        $category = $this->container->get('phpmyfaq.category');
        $currentGroups = $this->currentUser->perm->getUserGroups($this->currentUser->getUserId());

        // Handle bookmarks
        $bookmark = $this->container->get('phpmyfaq.bookmark');
        if ($bookmarkAction === 'add' && $faqId > 0) {
            $bookmark->add($faqId);
        }
        if ($bookmarkAction === 'remove' && $faqId > 0) {
            $bookmark->remove($faqId);
        }

        // Create a detail service
        $detailService = new FaqDisplayService(
            $this->configuration,
            $this->currentUser,
            $currentGroups,
            $faq,
            $category,
        );

        // Load FAQ data
        $faqId = $detailService->loadFaq($faqId, $solutionId);

        // Track visit
        $faqSession->userTracking('article_view', $faqId);
        $faqVisits = new Visits($this->configuration);
        $faqVisits->logViews($faqId);

        // Check if category and FAQ are linked
        if (!$category->categoryHasLinkToFaq($faqId, $cat)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        // Process content
        $currentUrl = sprintf('//%s%s', $request->getHost(), $request->getRequestUri());
        $question = $detailService->processQuestion($highlight);
        $answer = $detailService->processAnswer($currentUrl, $highlight);

        // Get related data
        $attachmentList = $detailService->getAttachmentList($faqId);
        $renderedCategoryPath = $detailService->getRenderedCategoryPath($faqId);
        $relatedFaqs = $detailService->getRelatedFaqs($faqId);
        $numComments = $detailService->getNumberOfComments();
        $comments = $detailService->getCommentsData($faqId);
        $availableLanguages = $detailService->getAvailableLanguages($faq->faqRecord['id']);
        $tagsHtml = $detailService->getTagsHtml($faqId);

        // Comment permissions
        $expired = $detailService->isExpired();
        $commentHelper = new CommentHelper();
        $commentHelper->setConfiguration($this->configuration);

        if (
            -1 === $this->currentUser->getUserId() && !$this->configuration->get('records.allowCommentsForGuests')
            || $faq->faqRecord['active'] === 'no'
            || 'n' === $faq->faqRecord['comment']
            || $expired
        ) {
            $commentMessage = Translation::get(key: 'msgWriteNoComment');
        } else {
            $commentMessage = sprintf(
                '%s<a href="#" data-bs-toggle="modal" data-bs-target="#pmf-modal-add-comment">%s</a>',
                Translation::get(key: 'msgYouCan'),
                Translation::get(key: 'msgWriteComment'),
            );
        }

        // Services for social sharing
        $faqServices = new Services($this->configuration);
        $faqServices->setCategoryId($cat);
        $faqServices->setFaqId($faqId);
        $faqServices->setLanguage($this->configuration->getLanguage()->getLanguage());
        $faqServices->setQuestion($question);

        // Author visibility (GDPR)
        $author = $this->currentUser->getUserVisibilityByEmail($faq->faqRecord['email'])
            ? $faq->faqRecord['author']
            : 'n/a';

        // SEO
        $seo = new Seo($this->configuration);
        $seoEntity = new SeoEntity();
        $seoEntity
            ->setSeoType(SeoType::FAQ)
            ->setReferenceId($faq->faqRecord['id'])
            ->setReferenceLanguage($faq->faqRecord['lang']);
        $seoData = $seo->get($seoEntity);

        // Date formatter
        $date = new Date($this->configuration);

        // Captcha
        $captcha = $this->container->get('phpmyfaq.captcha');
        $captchaHelper = $this->container->get('phpmyfaq.captcha.helper.captcha_helper');

        // Build template variables
        $templateVars = [
            ...$this->getHeader($request),
            'title' => sprintf('%s - %s', $seoData->getTitle() ?? $question, $this->configuration->getTitle()),
            'metaDescription' => $seoData->getDescription(),
            'solutionId' => $faq->faqRecord['solution_id'],
            'solutionIdLink' => Link::getSystemRelativeUri() . '?solution_id=' . $faq->faqRecord['solution_id'],
            'breadcrumb' => $category->getPathWithStartpage($cat, '/', true),
            'question' => $question,
            'answer' => $answer,
            'attachmentList' => $attachmentList,
            'faqDate' => $date->format($faq->faqRecord['created']),
            'faqLastChangeDate' => $date->format($faq->faqRecord['date']),
            'faqAuthor' => $author,
            'msgPdf' => Translation::get(key: 'msgPDF'),
            'msgPrintFaq' => Translation::get(key: 'msgPrintArticle'),
            'enableSendToFriend' => (bool) $this->configuration->get('main.enableSendToFriend'),
            'msgShareText' => Translation::get(key: 'msgShareText'),
            'msgShareViaWhatsapp' => Translation::get(key: 'msgShareViaWhatsapp'),
            'msgShareFAQ' => Translation::get(key: 'msgShareFAQ'),
            'linkToPdf' => $faqServices->getPdfLink(),
            'msgAverageVote' => Translation::get(key: 'msgAverageVote'),
            'renderVotingResult' => $detailService->getRating($faqId),
            'switchLanguage' => $detailService->getFaqHelper()->renderChangeLanguageSelector($faq, $cat),
            'msgVoteBad' => Translation::get(key: 'msgVoteBad'),
            'msgVoteGood' => Translation::get(key: 'msgVoteGood'),
            'msgVoteSubmit' => Translation::get(key: 'msgVoteSubmit'),
            'msgWriteComment' => Translation::get(key: 'msgWriteComment'),
            'id' => $faqId,
            'lang' => $this->configuration->getLanguage()->getLanguage(),
            'msgCommentHeader' => Translation::get(key: 'msgCommentHeader'),
            'msgNewContentName' => Translation::get(key: 'msgNewContentName'),
            'msgNewContentMail' => Translation::get(key: 'msgNewContentMail'),
            'defaultContentMail' => $this->currentUser->getUserId() > 0
                ? (string) $this->currentUser->getUserData('email')
                : '',
            'defaultContentName' => $this->currentUser->getUserId() > 0
                ? (string) $this->currentUser->getUserData('display_name')
                : '',
            'msgYourComment' => Translation::get(key: 'msgYourComment'),
            'msgCancel' => Translation::get(key: 'ad_gen_cancel'),
            'msgNewContentSubmit' => Translation::get(key: 'msgNewContentSubmit'),
            'csrfTokenAddComment' => Token::getInstance($this->container->get('session'))->getTokenString(
                'add-comment',
            ),
            'captchaFieldset' => $captchaHelper->renderCaptcha(
                $captcha,
                'writecomment',
                Translation::get(key: 'msgCaptcha'),
                $this->currentUser->isLoggedIn(),
            ),
            'renderComments' => $commentHelper->getComments($comments),
            'msg_about_faq' => Translation::get(key: 'msg_about_faq'),
            'userId' => $this->currentUser->getUserId(),
            'permissionEditFaq' => $this->currentUser->perm->hasPermission(
                $this->currentUser->getUserId(),
                PermissionType::FAQ_EDIT->value,
            ),
            'ad_entry_edit_1' => Translation::get(key: 'ad_entry_edit_1'),
            'ad_entry_edit_2' => Translation::get(key: 'ad_entry_edit_2'),
            'bookmarkAction' => $bookmarkAction ?? '',
            'msgBookmarkAdded' => Translation::get(key: 'msgBookmarkAdded'),
            'msgBookmarkRemoved' => Translation::get(key: 'msgBookmarkRemoved'),
            'csrfTokenRemoveBookmark' => Token::getInstance($this->container->get('session'))->getTokenString(
                'delete-bookmark',
            ),
            'csrfTokenAddBookmark' => Token::getInstance($this->container->get('session'))->getTokenString(
                'add-bookmark',
            ),
            'numberOfComments' => sprintf('%d %s', $numComments[$faqId] ?? 0, Translation::get(key: 'msgComments')),
            'writeCommentMsg' => $commentMessage,
        ];

        // Add conditional variables
        if (-1 !== $this->currentUser->getUserId()) {
            $templateVars['bookmarkIcon'] = $bookmark->isFaqBookmark($faqId) ? 'bi bi-bookmark-fill' : 'bi bi-bookmark';
            $templateVars['msgAddBookmark'] = $bookmark->isFaqBookmark($faqId)
                ? Translation::get(key: 'removeBookmark')
                : Translation::get(key: 'msgAddBookmark');
            $templateVars['isFaqBookmark'] = $bookmark->isFaqBookmark($faqId);
        }

        if ($availableLanguages !== [] && count($availableLanguages) > 1) {
            $templateVars['msgChangeLanguage'] = Translation::get(key: 'msgLanguageSubmit');
        }

        if (
            $this->currentUser->perm->hasPermission($this->currentUser->getUserId(), PermissionType::FAQ_EDIT->value)
            && isset($faq->faqRecord['notes'])
            && $faq->faqRecord['notes'] !== ''
        ) {
            $templateVars['notesHeader'] = Translation::get(key: 'ad_admin_notes');
            $templateVars['notes'] = $faq->faqRecord['notes'];
        }

        if ($tagsHtml !== '-') {
            $templateVars['renderTagsHeader'] = Translation::get(key: 'msg_tags');
            $templateVars['renderTags'] = $tagsHtml;
        }

        if ($renderedCategoryPath !== '') {
            $templateVars['renderRelatedCategoriesHeader'] = Translation::get(key: 'msgArticleCategories');
            $templateVars['renderRelatedCategories'] = $renderedCategoryPath;
        }

        if ($relatedFaqs !== '') {
            $templateVars['renderRelatedArticlesHeader'] = Translation::get(key: 'msg_related_articles');
            $templateVars['renderRelatedArticles'] = $relatedFaqs;
        }

        return $this->render('faq.twig', $templateVars);
    }
}
