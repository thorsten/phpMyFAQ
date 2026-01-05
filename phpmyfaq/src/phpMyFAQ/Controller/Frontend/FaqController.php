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
use phpMyFAQ\Language;
use phpMyFAQ\Link;
use phpMyFAQ\Link\Util\TitleSlugifier;
use phpMyFAQ\Seo;
use phpMyFAQ\Services;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\Extensions\LanguageCodeTwigExtension;
use phpMyFAQ\Utils;
use phpMyFAQ\Visits;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Extension\AttributeExtension;
use Twig\TwigFilter;

final class FaqController extends AbstractFrontController
{
    /**
     * Displays the form to add a new FAQ
     *
     * @throws Exception|LoaderError|\Exception
     */
    #[Route(path: '/add-faq.html', name: 'public.faq.add', methods: ['GET'])]
    public function add(Request $request): Response
    {
        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);
        $faqSession->userTracking('new_entry', 0);

        // Get current groups
        $currentGroups = $this->currentUser->perm->getUserGroups($this->currentUser->getUserId());

        $faqCreationService = new FaqCreationService($this->configuration, $this->currentUser, $currentGroups);

        if (!$faqCreationService->canUserAddFaq()) {
            if ($this->currentUser->getUserId() === -1) {
                return new RedirectResponse($this->configuration->getDefaultUrl() . 'login');
            }

            return new RedirectResponse($this->configuration->getDefaultUrl());
        }

        $selectedQuestion = Filter::filterVar($request->query->get('question'), FILTER_VALIDATE_INT);
        $selectedCategory = Filter::filterVar($request->query->get('cat'), FILTER_VALIDATE_INT, -1);

        $faqData = $faqCreationService->prepareAddFaqData($selectedQuestion, $selectedCategory);

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
            'defaultContentMail' => $faqCreationService->getDefaultUserEmail(),
            'defaultContentName' => $faqCreationService->getDefaultUserName(),
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

        $this->addExtension(new AttributeExtension(LanguageCodeTwigExtension::class));
        return $this->render('add.twig', $templateVars);
    }

    /**
     * Redirects solution_id URLs to the actual FAQ page
     *
     * @throws Exception|\Exception
     */
    #[Route(path: '/solution_id_{solutionId}.html', name: 'public.faq.solution', methods: ['GET'])]
    public function solution(Request $request): Response
    {
        $solutionId = Filter::filterVar($request->attributes->get('solutionId'), FILTER_VALIDATE_INT, 0);

        if ($solutionId === 0) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $faq = $this->container->get('phpmyfaq.faq');
        $faqData = $faq->getIdFromSolutionId($solutionId);

        if (empty($faqData)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $slug = TitleSlugifier::slug($faqData['question']);

        // Redirect to the canonical FAQ URL
        $url = sprintf('/content/%d/%d/%s/%s.html', $faqData['category_id'], $faqData['id'], $faqData['lang'], $slug);

        return new RedirectResponse($url, Response::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * Redirects short content URLs to the full FAQ page
     *
     * @throws Exception|\Exception
     */
    #[Route(path: '/content/{faqId}/{faqLang}', name: 'public.faq.redirect', methods: ['GET'])]
    public function contentRedirect(Request $request): Response
    {
        $faqId = Filter::filterVar($request->attributes->get('faqId'), FILTER_VALIDATE_INT, 0);
        $faqLang = Filter::filterVar($request->attributes->get('faqLang'), FILTER_SANITIZE_SPECIAL_CHARS);

        if ($faqId === 0 || empty($faqLang)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $faq = $this->container->get('phpmyfaq.faq');

        // Query the FAQ data directly for the specified language
        $result = $faq->getFaqResult($faqId, $faqLang);

        if ($this->configuration->getDb()->numRows($result) === 0) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $row = $this->configuration->getDb()->fetchObject($result);
        if (!$row) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $category = $this->container->get('phpmyfaq.category');
        $categoryId = $category->getCategoryIdFromFaq($faqId);

        if ($categoryId === 0) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $slug = TitleSlugifier::slug($row->thema);

        // Redirect to the canonical FAQ URL
        $url = sprintf('/content/%d/%d/%s/%s.html', $categoryId, $faqId, $faqLang, $slug);

        return new RedirectResponse($url, Response::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * Displays a single FAQ article with comments, ratings, and related content
     *
     * @throws Exception|LoaderError|\Exception
     */
    #[Route(path: '/content/{categoryId}/{faqId}/{faqLang}/{slug}.html', name: 'public.faq.show', methods: ['GET'])]
    public function show(Request $request): Response
    {
        $faqSession = $this->container->get('phpmyfaq.user.session');
        $faqSession->setCurrentUser($this->currentUser);

        // Get parameters
        $categoryId = Filter::filterVar($request->attributes->get('categoryId'), FILTER_VALIDATE_INT, 0);

        // Get faqId from route attributes (new routes) or query parameters (legacy/backward compatibility)
        $faqId = Filter::filterVar($request->attributes->get('faqId'), FILTER_VALIDATE_INT);

        // Get language from route parameter (for /content/ URLs)
        $requestedLanguage =
            Filter::filterVar(
                $request->attributes->get('language'),
                FILTER_SANITIZE_SPECIAL_CHARS,
            ) ?? Filter::filterVar(
                $request->attributes->get('faqLang'),
                FILTER_SANITIZE_SPECIAL_CHARS,
            ) ?? $this->configuration->getLanguage()->getLanguage();

        // Temporarily set the language in session for this request
        $session = $this->container->get('session');
        $originalLanguage = $session->get('lang');
        if ($requestedLanguage !== $originalLanguage) {
            $session->set('lang', $requestedLanguage);
            // Update the static language variable
            Language::$language = $requestedLanguage;
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
        $faqDisplayService = new FaqDisplayService(
            $this->configuration,
            $this->currentUser,
            $currentGroups,
            $faq,
            $category,
        );

        // Load FAQ data
        $faqId = $faqDisplayService->loadFaq($faqId, $solutionId);

        // Track visit
        $faqSession->userTracking('article_view', $faqId);
        $faqVisits = new Visits($this->configuration);
        $faqVisits->logViews($faqId);

        // Check if category and FAQ are linked
        if (!$category->categoryHasLinkToFaq($faqId, $categoryId)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        // Process content
        $currentUrl = sprintf('//%s%s', $request->getHost(), $request->getRequestUri());
        $question = $faqDisplayService->processQuestion($highlight);
        $answer = $faqDisplayService->processAnswer($currentUrl, $highlight);

        // Get related data
        $attachmentList = $faqDisplayService->getAttachmentList($faqId);
        $renderedCategoryPath = $faqDisplayService->getRenderedCategoryPath($faqId);
        $relatedFaqs = $faqDisplayService->getRelatedFaqs($faqId);
        $numComments = $faqDisplayService->getNumberOfComments();
        $comments = $faqDisplayService->getCommentsData($faqId);
        $availableLanguages = $faqDisplayService->getAvailableLanguages($faq->faqRecord['id']);
        $tagsHtml = $faqDisplayService->getTagsHtml($faqId);

        // Generate language URLs with SEO slugs
        $languageUrls = [];
        foreach ($availableLanguages as $language) {
            $url = sprintf(
                '%scontent/%d/%d/%s/%s.html',
                $this->configuration->getDefaultUrl(),
                $categoryId,
                $faqId,
                $language,
                TitleSlugifier::slug($question),
            );
            $link = new Link($url, $this->configuration);
            $link->setTitle($question);
            $languageUrls[$language] = $link->toString();
        }

        // Comment permissions
        $expired = $faqDisplayService->isExpired();

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
        $faqServices->setCategoryId($categoryId);
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
            'solutionIdLink' => './solution_id_' . $faq->faqRecord['solution_id'] . '.html',
            'breadcrumb' => $category->getPathWithStartpage($categoryId, '/', true),
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
            'renderVotingResult' => $faqDisplayService->getRating($faqId),
            'languageUrls' => $languageUrls,
            'currentLanguage' => $faq->faqRecord['lang'],
            'msgVoteBad' => Translation::get(key: 'msgVoteBad'),
            'msgVoteGood' => Translation::get(key: 'msgVoteGood'),
            'msgVoteSubmit' => Translation::get(key: 'msgVoteSubmit'),
            'msgWriteComment' => Translation::get(key: 'msgWriteComment'),
            'id' => $faqId,
            'lang' => $this->configuration->getLanguage()->getLanguage(),
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
            'comments' => $this->prepareCommentsData($comments),
            'msgShowMore' => Translation::get(key: 'msgShowMore'),
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

        $this->addExtension(new AttributeExtension(LanguageCodeTwigExtension::class));
        return $this->render('faq.twig', $templateVars);
    }

    /**
     * Prepares comment data for the Twig macro
     *
     * @param array $comments Array of Comment objects
     * @throws \Exception
     * @return array
     */
    private function prepareCommentsData(array $comments): array
    {
        $date = $this->container->get('phpmyfaq.date');
        $mail = $this->container->get('phpmyfaq.mail');
        $gravatar = $this->container->get('phpmyfaq.services.gravatar');

        $preparedComments = [];
        $gravatarImages = [];
        $safeEmails = [];
        $formattedDates = [];

        foreach ($comments as $comment) {
            $commentId = $comment->getId();
            $preparedComments[] = [
                'id' => $commentId,
                'email' => $comment->getEmail(),
                'username' => Strings::htmlentities($comment->getUsername()),
                'date' => $comment->getDate(),
                'comment' => Utils::parseUrl($comment->getComment()),
            ];

            $gravatarImages[$commentId] = $gravatar->getImage($comment->getEmail(), ['class' => 'img-thumbnail']);
            $safeEmails[$commentId] = $mail->safeEmail($comment->getEmail());
            $formattedDates[$commentId] = $date->format($comment->getDate());
        }

        return [
            'comments' => $preparedComments,
            'gravatarImages' => $gravatarImages,
            'safeEmails' => $safeEmails,
            'formattedDates' => $formattedDates,
        ];
    }
}
