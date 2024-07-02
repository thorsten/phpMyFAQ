<?php

/**
 * Shows the page with the FAQ record and - when available - the user comments.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Lars Tiedemann <larstiedemann@yahoo.de>
 * @copyright 2002-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-08-27
 */

use phpMyFAQ\Component\Alert;
use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Bookmark;
use phpMyFAQ\Captcha\Captcha;
use phpMyFAQ\Captcha\Helper\CaptchaHelper;
use phpMyFAQ\Comments;
use phpMyFAQ\Date;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Faq\Permission;
use phpMyFAQ\Filter;
use phpMyFAQ\Glossary;
use phpMyFAQ\Helper\AttachmentHelper;
use phpMyFAQ\Helper\CommentHelper;
use phpMyFAQ\Helper\FaqHelper as HelperFaq;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Link;
use phpMyFAQ\Rating;
use phpMyFAQ\Relation;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Services;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Tags;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Utils;
use phpMyFAQ\Visits;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Extension\DebugExtension;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$glossary = new Glossary($faqConfig);
$tagging = new Tags($faqConfig);
$relation = new Relation($faqConfig);
$rating = new Rating($faqConfig);
$comment = new Comments($faqConfig);
$markDown = new ParsedownExtra();
$faqHelper = new HelperFaq($faqConfig);
$faqPermission = new Permission($faqConfig);
$attachmentHelper = new AttachmentHelper();

if (is_null($user)) {
    $user = new CurrentUser($faqConfig);
}

$templateVars = [];

$faqSearchResult = new SearchResultSet($user, $faqPermission, $faqConfig);

$captcha = Captcha::getInstance($faqConfig);
$captcha->setSessionId($sids);

$currentCategory = $cat;

$request = Request::createFromGlobals();
$faqId = Filter::filterVar($request->query->get('id'), FILTER_VALIDATE_INT, 0);
$solutionId = Filter::filterVar($request->query->get('solution_id'), FILTER_VALIDATE_INT);
$highlight = Filter::filterVar($request->query->get('highlight'), FILTER_SANITIZE_SPECIAL_CHARS);
$bookmarkAction = Filter::filterVar($request->query->get('bookmark_action'), FILTER_SANITIZE_SPECIAL_CHARS);

// Handle bookmarks
$bookmark = new Bookmark($faqConfig, $user);
if ($bookmarkAction === 'add' && isset($faqId)) {
    $bookmark->saveFaqAsBookmarkById($faqId);
}

if ($bookmarkAction === 'remove' && isset($faqId)) {
    $bookmark->remove($faqId);
}

// Get all data from the FAQ record
if (0 === (int)$solutionId) {
    $faq->getRecord($faqId);
} else {
    $faq->getRecordBySolutionId($solutionId);
}

if (isset($faq->faqRecord['id'])) {
    $faqId = $faq->faqRecord['id'];
}

$faqSession->userTracking('article_view', $faqId);

$faqVisits = new Visits($faqConfig);
$faqVisits->logViews((int) $faqId);

$question = $faq->getQuestion($faqId);
$question = Strings::htmlentities($question);
if ($faqConfig->get('main.enableMarkdownEditor')) {
    $answer = $markDown->text($faq->faqRecord['content']);
} else {
    $answer = $faqHelper->renderMarkupContent($faq->faqRecord['content']);
}

// Cleanup answer content first
$answer = $faqHelper->cleanUpContent($answer);

// Rewrite URL fragments
$currentUrl = htmlspecialchars(sprintf('//%s%s', $request->getHost(), $request->getRequestUri()), ENT_QUOTES, 'UTF-8');
$answer = $faqHelper->rewriteUrlFragments($answer, $currentUrl);

// Add Glossary entries for answers only
$answer = $glossary->insertItemsIntoContent($answer);

// Set the path of the current category
$categoryName = $category->getPath($currentCategory, ' &raquo; ', true);

if (
    !is_null($highlight) && $highlight != '/' && $highlight != '<' && $highlight != '>' && Strings::strlen(
        $highlight
    ) > 3
) {
    $highlight = str_replace("'", '´', (string) $highlight);
    $highlight = str_replace(['^', '.', '?', '*', '+', '{', '}', '(', ')', '[', ']'], '', $highlight);
    $highlight = preg_quote($highlight, '/');
    $searchItems = explode(' ', $highlight);

    foreach ($searchItems as $searchItem) {
        if (Strings::strlen($searchItem) > 2) {
            $question = Utils::setHighlightedString($question, $searchItem);
            $answer = Utils::setHighlightedString($answer, $searchItem);
        }
    }
}

// List all faq attachments
if ($faqConfig->get('records.disableAttachments') && 'yes' == $faq->faqRecord['active']) {
    try {
        $attList = AttachmentFactory::fetchByRecordId($faqConfig, $faqId);
        $answer .= $attachmentHelper->renderAttachmentList($attList);
    } catch (AttachmentException) {
        // handle exception
    }
}

// List all categories for this faq
$renderedCategoryPath = '';
$multiCategories = $category->getCategoriesFromFaq($faqId);
if ((is_countable($multiCategories) ? count($multiCategories) : 0) > 1) {
    foreach ($multiCategories as $multiCategory) {
        $path = $category->getPath($multiCategory['id'], ' &raquo; ', true, 'breadcrumb-related-categories');
        if ('' === trim((string) $path)) {
            continue;
        }

        $renderedCategoryPath .= $path;
    }
}

// Related FAQs
try {
    $faqSearchResult->reviewResultSet(
        $relation->getAllRelatedByQuestion(
            Strings::htmlentities($faq->faqRecord['title']),
            Strings::htmlentities($faq->faqRecord['keywords'])
        )
    );
} catch (Exception) {
    // handle exception
}

$searchHelper = new SearchHelper($faqConfig);
$relatedFaqs = $searchHelper->renderRelatedFaqs($faqSearchResult, $faqId);

// Show link to edit the faq?
$editThisEntry = '';
if ($user->perm->hasPermission($user->getUserId(), PermissionType::FAQ_EDIT->value)) {
    $editThisEntry = sprintf(
        '<i aria-hidden="true" class="bi bi-pencil"></i> ' .
        '<a class="text-decoration-none" href="./admin/index.php?action=editentry&id=%d&lang=%s">%s</a>',
        $faqId,
        $lang,
        Translation::get('ad_entry_edit_1') . ' ' . Translation::get('ad_entry_edit_2')
    );
}

// Is the faq expired?
$expired = (date('YmdHis') > $faq->faqRecord['dateEnd']);

// Number of comments
$numComments = $comment->getNumberOfComments();
$comments = $comment->getCommentsData($faqId, CommentType::FAQ);

$commentHelper = new CommentHelper();
$commentHelper->setConfiguration($faqConfig);

// Does the user have the right to add a comment?
if (
    (-1 === $user->getUserId() && !$faqConfig->get('records.allowCommentsForGuests')) ||
    ($faq->faqRecord['active'] === 'no') || ('n' === $faq->faqRecord['comment']) || $expired
) {
    $commentMessage = Translation::get('msgWriteNoComment');
} else {
    $commentMessage = sprintf(
        '%s<a href="#" data-bs-toggle="modal" data-bs-target="#pmf-modal-add-comment">%s</a>',
        Translation::get('msgYouCan'),
        Translation::get('msgWriteComment')
    );
    $templateVars = [
        'numberOfComments' => sprintf('%d %s', $numComments[$faqId] ?? 0, Translation::get('ad_start_comments'))
    ];
}

// Display ability for saving bookmarks only for registered users
if (-1 !== $user->getUserId()) {
    $templateVars = [
        ...$templateVars,
        'bookmarkIcon' => $bookmark->isFaqBookmark($faqId) ? 'bi bi-bookmark-fill' : 'bi bi-bookmark',
        'bookmarkLink' =>
            $bookmark->isFaqBookmark($faqId)
                ?
                sprintf('index.php?action=faq&bookmark_action=remove&id=%d', $faqId)
                :
                sprintf('index.php?action=faq&bookmark_action=add&id=%d', $faqId),
        'msgAddBookmark' =>
            $bookmark->isFaqBookmark($faqId) ? Translation::get('removeBookmark') : Translation::get('msgAddBookmark')
    ];
}

$availableLanguages = $faqConfig->getLanguage()->isLanguageAvailable($faq->faqRecord['id']);

if (!empty($availableLanguages) && (is_countable($availableLanguages) ? count($availableLanguages) : 0) > 1) {
    $templateVars = [
        ...$templateVars,
        'msgChangeLanguage' => Translation::get('msgLanguageSubmit')
    ];
}

if (
    $user->perm->hasPermission($user->getUserId(), PermissionType::FAQ_EDIT->value) &&
    !empty($faq->faqRecord['notes'])
) {
    $templateVars = [
        ...$templateVars,
        'notesHeader' => Translation::get('ad_admin_notes'),
        'notes' => $faq->faqRecord['notes']
    ];
}

if ('-' !== $tagging->getAllLinkTagsById($faqId)) {
    $templateVars = [
        ...$templateVars,
        'renderTagsHeader' => Translation::get('msg_tags'),
        'renderTags' =>  $tagging->getAllLinkTagsById($faqId),
    ];
}

if ('' !== $renderedCategoryPath) {
    $templateVars = [
        ...$templateVars,
        'renderRelatedCategoriesHeader' => Translation::get('msgArticleCategories'),
        'renderRelatedCategories' => $renderedCategoryPath,
    ];
}

if ('' !== $relatedFaqs) {
    $templateVars = [
        ...$templateVars,
        'renderRelatedArticlesHeader' => Translation::get('msg_related_articles'),
        'renderRelatedArticles' => $relatedFaqs,
    ];
}

$date = new Date($faqConfig);
$captchaHelper = CaptchaHelper::getInstance($faqConfig);

// We need some Links from social networks
$faqServices = new Services($faqConfig);
$faqServices->setCategoryId($cat);
$faqServices->setFaqId($id);
$faqServices->setLanguage($lang);
$faqServices->setQuestion($faq->getQuestion($id));

// Check if category ID and FAQ ID are linked together
if (!$category->categoryHasLinkToFaq($faqId, $currentCategory)) {
    $response = new Response();
    $response->setStatusCode(Response::HTTP_NOT_FOUND);
}

// Check if the author name should be visible, according to the GDPR option
$author = $user->getUserVisibilityByEmail($faq->faqRecord['email']) ? $faq->faqRecord['author'] : 'n/a';

$templateVars = [
    ...$templateVars,
    'baseHref' => $faqSystem->getSystemUri($faqConfig),
    'solutionId' => $faq->faqRecord['solution_id'],
    'solutionIdLink' => Link::getSystemRelativeUri() . '?solution_id=' . $faq->faqRecord['solution_id'],
    'question' => $question,
    'answer' => $answer,
    'faqDate' => $date->format($faq->faqRecord['date']),
    'faqAuthor' => Strings::htmlentities($author),
    'editThisEntry' => $editThisEntry,
    'msgPdf' => Translation::get('msgPDF'),
    'msgPrintFaq' => Translation::get('msgPrintArticle'),
    'sendToFriendViaEmail' => $faqHelper->renderSendToFriendViaEmail($faqServices->getSuggestLink()),
    'sendToFriendViaWhatsapp' => $faqHelper->renderSendToFriendViaWhatsapp($faqServices->getSuggestLink()),
    'linkToPdf' => $faqServices->getPdfLink(),
    'saveVotingID' => $faqId,
    'msgAverageVote' => Translation::get('msgAverageVote'),
    'renderVotingResult' => $rating->get($faqId),
    'switchLanguage' => $faqHelper->renderChangeLanguageSelector($faq, $currentCategory),
    'msgVoteUsability' => Translation::get('msgVoteUsability'),
    'msgVoteBad' => Translation::get('msgVoteBad'),
    'msgVoteGood' => Translation::get('msgVoteGood'),
    'msgVoteSubmit' => Translation::get('msgVoteSubmit'),
    'writeCommentMsg' => $commentMessage,
    'msgWriteComment' => Translation::get('msgWriteComment'),
    'id' => $faqId,
    'lang' => $lang,
    'msgCommentHeader' => Translation::get('msgCommentHeader'),
    'msgNewContentName' => Translation::get('msgNewContentName'),
    'msgNewContentMail' => Translation::get('msgNewContentMail'),
    'defaultContentMail' => ($user->getUserId() > 0) ? $user->getUserData('email') : '',
    'defaultContentName' =>
        ($user->getUserId() > 0) ? Strings::htmlentities($user->getUserData('display_name')) : '',
    'msgYourComment' => Translation::get('msgYourComment'),
    'msgCancel' => Translation::get('ad_gen_cancel'),
    'msgNewContentSubmit' => Translation::get('msgNewContentSubmit'),
    'csrfInput' => Token::getInstance()->getTokenInput('add-comment'),
    'captchaFieldset' => $captchaHelper->renderCaptcha(
        $captcha,
        'writecomment',
        Translation::get('msgCaptcha'),
        $user->isLoggedIn()
    ),
    'renderComments' => $commentHelper->getComments($comments),
    'msg_about_faq' => Translation::get('msg_about_faq'),
    'userId' => $user->getUserId(),
];

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$twig->addExtension(new DebugExtension());
$twigTemplate = $twig->loadTemplate('./faq.twig');

$template->addRenderedTwigOutput(
    'mainPageContent',
    $twigTemplate->render($templateVars)
);
