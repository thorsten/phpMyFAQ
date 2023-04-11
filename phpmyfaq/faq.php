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
 * @copyright 2002-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-08-27
 */

use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Captcha\Captcha;
use phpMyFAQ\Captcha\Helper\CaptchaHelper;
use phpMyFAQ\Comments;
use phpMyFAQ\Date;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Faq\FaqPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Glossary;
use phpMyFAQ\Helper\AttachmentHelper;
use phpMyFAQ\Helper\FaqHelper as HelperFaq;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Link;
use phpMyFAQ\LinkVerifier;
use phpMyFAQ\Rating;
use phpMyFAQ\Relation;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Services;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Tags;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Utils;
use phpMyFAQ\Visits;

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
$faqPermission = new FaqPermission($faqConfig);
$attachmentHelper = new AttachmentHelper();

if (is_null($user)) {
    $user = new CurrentUser($faqConfig);
}

$faqSearchResult = new SearchResultSet($user, $faqPermission, $faqConfig);

$captcha = Captcha::getInstance($faqConfig);
$captcha->setSessionId($sids);
if (!is_null($showCaptcha)) {
    $captcha->drawCaptchaImage();
    exit;
}

$currentCategory = $cat;

$faqId = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$solutionId = Filter::filterInput(INPUT_GET, 'solution_id', FILTER_VALIDATE_INT);

// Get all data from the FAQ record
if (0 === (int)$solutionId) {
    $faq->getRecord($faqId);
} else {
    $faq->getRecordBySolutionId($solutionId);
}

if (isset($faq->faqRecord['id'])) {
    $faqId = $faq->faqRecord['id'];
}

try {
    $faqSession->userTracking('article_view', $faqId);
} catch (Exception) {
    // @todo handle the exception
}

$faqVisits = new Visits($faqConfig);
$faqVisits->logViews((int) $faqId);

$question = $faq->getRecordTitle($faqId);
$question = Strings::htmlentities($question);
if ($faqConfig->get('main.enableMarkdownEditor')) {
    $answer = $markDown->text($faq->faqRecord['content']);
} else {
    $answer = $faqHelper->renderMarkupContent($faq->faqRecord['content']);
}

$answer = $faqHelper->cleanUpContent($answer);

// Rewrite URL fragments
$currentUrl = htmlspecialchars("//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}", ENT_QUOTES, 'UTF-8');
$answer = $faqHelper->rewriteUrlFragments($answer, $currentUrl);

// Add Glossary entries for answers only
$answer = $glossary->insertItemsIntoContent($answer);

// Set the path of the current category
$categoryName = $category->getPath($currentCategory, ' &raquo; ', true);

$highlight = Filter::filterInput(INPUT_GET, 'highlight', FILTER_SANITIZE_SPECIAL_CHARS);
if (
    !is_null($highlight) && $highlight != '/' && $highlight != '<' && $highlight != '>' && Strings::strlen(
        $highlight
    ) > 3
) {
    $highlight = str_replace("'", '´', (string) $highlight);
    $highlight = str_replace(['^', '.', '?', '*', '+', '{', '}', '(', ')', '[', ']'], '', $highlight);
    $highlight = preg_quote($highlight, '/');
    $searchItems = explode(' ', $highlight);

    foreach ($searchItems as $item) {
        if (Strings::strlen($item) > 2) {
            $question = Utils::setHighlightedString($question, $item);
            $answer = Utils::setHighlightedString($answer, $item);
        }
    }
}

$linkVerifier = new LinkVerifier($faqConfig);
$linkArray = $linkVerifier->getUrlPool();
if (isset($linkArray['href'])) {
    foreach (array_unique($linkArray['href']) as $_url) {
        $xpos = strpos((string) $_url, 'index.php?action=faq');
        if (!($xpos === false)) {
            // Get the FaqHelper link title
            $matches = [];
            preg_match('/id=([\d]+)/ism', (string) $_url, $matches);
            $_id = $matches[1];
            $_title = $faq->getRecordTitle($_id);
            $_link = substr((string) $_url, $xpos + 9);
            if (!str_contains((string) $_url, '&amp;')) {
                $_link = str_replace('&', '&amp;', $_link);
            }
            $oLink = new Link($faqConfig->getDefaultUrl() . $_link, $faqConfig);
            $oLink->itemTitle = $oLink->tooltip = $_title;
            $newFaqPath = $oLink->toString();
            $answer = str_replace($_url, $newFaqPath, (string) $answer);
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
    foreach ($multiCategories as $multiCat) {
        $path = $category->getPath($multiCat['id'], ' &raquo; ', true, 'breadcrumb-related-categories');
        if ('' === trim($path)) {
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
if ($user->perm->hasPermission($user->getUserId(), 'edit_faq')) {
    $editThisEntry = sprintf(
        '<i aria-hidden="true" class="fa fa-pencil"></i> ' .
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
    $template->parseBlock(
        'mainPageContent',
        'enableComments',
        [
            'numberOfComments' => sprintf('%d %s', $numComments[$faqId] ?? 0, Translation::get('ad_start_comments')),
        ]
    );
}

$translationUrl = sprintf(
    str_replace(
        '%',
        '%%',
        Link::getSystemRelativeUri('index.php')
    ) . 'index.php?%saction=translate&amp;cat=%s&amp;id=%d&amp;srclang=%s',
    $sids,
    $currentCategory,
    $faqId,
    $lang
);

$availableLanguages = $faqConfig->getLanguage()->isLanguageAvailable($faq->faqRecord['id']);

if (!empty($availableLanguages) && (is_countable($availableLanguages) ? count($availableLanguages) : 0) > 1) {
    $template->parseBlock(
        'mainPageContent',
        'switchLanguage',
        [
            'msgChangeLanguage' => Translation::get('msgLanguageSubmit'),
        ]
    );
}

if (
    $user->perm->hasPermission($user->getUserId(), 'addtranslation') &&
    !empty($availableLanguages) && (is_countable($availableLanguages) ? count($availableLanguages) : 0) > 1
) {
    $template->parseBlock(
        'mainPageContent',
        'addTranslation',
        [
            'msgTranslate' => Translation::get('msgTranslate'),
        ]
    );
}

if ($user->perm->hasPermission($user->getUserId(), 'edit_faq') && !empty($faq->faqRecord['notes'])) {
    $template->parseBlock(
        'mainPageContent',
        'privateNotes',
        [
            'notesHeader' => Translation::get('ad_admin_notes'),
            'notes' => $faq->faqRecord['notes']
        ]
    );
}

if ('-' !== $tagging->getAllLinkTagsById($faqId)) {
    $template->parseBlock(
        'mainPageContent',
        'tagsAvailable',
        [
            'renderTagsHeader' => Translation::get('msg_tags'),
            'renderTags' =>  $tagging->getAllLinkTagsById($faqId),
        ]
    );
}

if ('' !== $renderedCategoryPath) {
    $template->parseBlock(
        'mainPageContent',
        'relatedCategories',
        [
            'renderRelatedCategoriesHeader' => Translation::get('msgArticleCategories'),
            'renderRelatedCategories' => $renderedCategoryPath,
        ]
    );
}

if ('' !== $relatedFaqs) {
    $template->parseBlock(
        'mainPageContent',
        'relatedFaqs',
        [
            'renderRelatedArticlesHeader' => Translation::get('msg_related_articles'),
            'renderRelatedArticles' => $relatedFaqs,
        ]
    );
}

$date = new Date($faqConfig);
$captchaHelper = CaptchaHelper::getInstance($faqConfig);

// We need some Links from social networks
$faqServices = new Services($faqConfig);
$faqServices->setCategoryId($cat);
$faqServices->setFaqId($id);
$faqServices->setLanguage($lang);
$faqServices->setQuestion($faq->getRecordTitle($id));

// Check if category ID and FAQ ID are linked together
if (!$category->categoryHasLinkToFaq($faqId, $currentCategory)) {
    $http->setStatus(404);
}

// Check if author name should be visible according to GDPR option
if ($user->getUserVisibilityByEmail($faq->faqRecord['email'])) {
    $author = $faq->faqRecord['author'];
} else {
    $author = 'n/a';
}

$template->parse(
    'mainPageContent',
    [
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
        'sendToFriend' => $faqHelper->renderSendToFriend($faqServices->getSuggestLink()),
        'shareOnTwitter' => $faqHelper->renderTwitterShareLink($faqServices->getShareOnTwitterLink()),
        'linkToPdf' => $faqServices->getPdfLink(),
        'translationUrl' => $translationUrl,
        'languageSelection' => LanguageHelper::renderSelectLanguage(
            $faqLangCode,
            false,
            $availableLanguages,
            'translation'
        ),
        'msgTranslateSubmit' => Translation::get('msgTranslateSubmit'),
        'saveVotingID' => $faqId,
        'msgAverageVote' => Translation::get('msgAverageVote'),
        'renderVotingResult' => $rating->getVotingResult($faqId),
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
        'defaultContentMail' => ($user instanceof CurrentUser) ? $user->getUserData('email') : '',
        'defaultContentName' => ($user instanceof CurrentUser) ? $user->getUserData('display_name') : '',
        'msgYourComment' => Translation::get('msgYourComment'),
        'msgCancel' => Translation::get('ad_gen_cancel'),
        'msgNewContentSubmit' => Translation::get('msgNewContentSubmit'),
        'csrfInput' => Token::getInstance()->getTokenInput('add-comment'),
        'captchaFieldset' =>
            $captchaHelper->renderCaptcha($captcha, 'writecomment', Translation::get('msgCaptcha'), $user->isLoggedIn()),
        'renderComments' => $comment->getComments($faqId, CommentType::FAQ),
        'msg_about_faq' => Translation::get('msg_about_faq'),
    ]
);

$template->parseBlock(
    'index',
    'breadcrumb',
    [
        'breadcrumbHeadline' => $categoryName
    ]
);
