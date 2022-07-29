<?php

/**
 * Shows the page with the FAQ record and - when available - the user comments.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Lars Tiedemann <larstiedemann@yahoo.de>
 * @copyright 2002-2022 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2002-08-27
 */

use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Captcha;
use phpMyFAQ\Comments;
use phpMyFAQ\Date;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Faq\FaqPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Glossary;
use phpMyFAQ\Helper\AttachmentHelper;
use phpMyFAQ\Helper\CaptchaHelper;
use phpMyFAQ\Helper\FaqHelper as HelperFaq;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Link;
use phpMyFAQ\LinkVerifier;
use phpMyFAQ\Rating;
use phpMyFAQ\Relation;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Services;
use phpMyFAQ\Strings;
use phpMyFAQ\Tags;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Utils;
use phpMyFAQ\Visits;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$captcha = new Captcha($faqConfig);
$oGlossary = new Glossary($faqConfig);
$faqTagging = new Tags($faqConfig);
$faqRelation = new Relation($faqConfig);
$faqRating = new Rating($faqConfig);
$faqComment = new Comments($faqConfig);
$markDown = new \ParsedownExtra();
$faqHelper = new HelperFaq($faqConfig);
$faqPermission = new FaqPermission($faqConfig);
$attachmentHelper = new AttachmentHelper();

if (is_null($user)) {
    $user = new CurrentUser($faqConfig);
}

$faqSearchResult = new SearchResultSet($user, $faqPermission, $faqConfig);

$captcha->setSessionId($sids);
if (!is_null($showCaptcha)) {
    $captcha->drawCaptchaImage();
    exit;
}

$currentCategory = $cat;

$recordId = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$solutionId = Filter::filterInput(INPUT_GET, 'solution_id', FILTER_VALIDATE_INT);

// Get all data from the FAQ record
if (0 === (int)$solutionId) {
    $faq->getRecord($recordId);
} else {
    $faq->getRecordBySolutionId($solutionId);
}

if (isset($faq->faqRecord['id'])) {
    $recordId = $faq->faqRecord['id'];
}

try {
    $faqSession->userTracking('article_view', $recordId);
} catch (Exception $e) {
    // @todo handle the exception
}

$faqVisits = new Visits($faqConfig);
$faqVisits->logViews((int) $recordId);

$question = $faq->getRecordTitle($recordId);
if ($faqConfig->get('main.enableMarkdownEditor')) {
    $answer = $markDown->text($faq->faqRecord['content']);
} else {
    $answer = $faqHelper->renderMarkupContent($faq->faqRecord['content']);
}

// Rewrite URL fragments
$currentUrl = htmlspecialchars("//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}", ENT_QUOTES, 'UTF-8');
$answer = $faqHelper->rewriteUrlFragments($answer, $currentUrl);

// Add Glossary entries for answers only
$answer = $oGlossary->insertItemsIntoContent($answer);

// Set the path of the current category
$categoryName = $category->getPath($currentCategory, ' &raquo; ', true, '');

$highlight = Filter::filterInput(INPUT_GET, 'highlight', FILTER_UNSAFE_RAW);
if (
    !is_null($highlight) && $highlight != '/' && $highlight != '<' && $highlight != '>' && Strings::strlen(
        $highlight
    ) > 3
) {
    $highlight = str_replace("'", '´', $highlight);
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
        $xpos = strpos($_url, 'index.php?action=faq');
        if (!($xpos === false)) {
            // Get the FaqHelper link title
            $matches = [];
            preg_match('/id=([\d]+)/ism', $_url, $matches);
            $_id = $matches[1];
            $_title = $faq->getRecordTitle($_id);
            $_link = substr($_url, $xpos + 9);
            if (strpos($_url, '&amp;') === false) {
                $_link = str_replace('&', '&amp;', $_link);
            }
            $oLink = new Link($faqConfig->getDefaultUrl() . $_link, $faqConfig);
            $oLink->itemTitle = $oLink->tooltip = $_title;
            $newFaqPath = $oLink->toString();
            $answer = str_replace($_url, $newFaqPath, $answer);
        }
    }
}

// List all faq attachments
if ($faqConfig->get('records.disableAttachments') && 'yes' == $faq->faqRecord['active']) {
    try {
        $attList = AttachmentFactory::fetchByRecordId($faqConfig, $recordId);
        $answer .= $attachmentHelper->renderAttachmentList($attList);
    } catch (AttachmentException $e) {
        // handle exception
    }
}

// List all categories for this faq
$htmlAllCategories = '';
$multiCategories = $category->getCategoriesFromFaq($recordId);
if (count($multiCategories) > 1) {
    foreach ($multiCategories as $multiCat) {
        $path = $category->getPath($multiCat['id'], ' &raquo; ', true, 'breadcrumb-related-categories');
        if ('' === trim($path)) {
            continue;
        }
        $htmlAllCategories .= $path;
    }
}

// Related FAQs
$faqSearchResult->reviewResultSet(
    $faqRelation->getAllRelatedByQuestion(
        $faq->faqRecord['title'],
        $faq->faqRecord['keywords']
    )
);

$searchHelper = new SearchHelper($faqConfig);
$relatedFaqs = $searchHelper->renderRelatedFaqs($faqSearchResult, $recordId);

// Show link to edit the faq?
$editThisEntry = '';
if ($user->perm->hasPermission($user->getUserId(), 'edit_faq')) {
    $editThisEntry = sprintf(
        '<i aria-hidden="true" class="fa fa-pencil"></i> <a class="data" href="./admin/index.php?action=editentry&id=%d&lang=%s">%s</a>',
        $recordId,
        $lang,
        $PMF_LANG['ad_entry_edit_1'] . ' ' . $PMF_LANG['ad_entry_edit_2']
    );
}

// Is the faq expired?
$expired = (date('YmdHis') > $faq->faqRecord['dateEnd']);

// Number of comments
$numComments = $faqComment->getNumberOfComments();

// Does the user have the right to add a comment?
if (
    (-1 === $user->getUserId() && !$faqConfig->get('records.allowCommentsForGuests')) ||
    ($faq->faqRecord['active'] === 'no') || ('n' === $faq->faqRecord['comment']) || $expired
) {
    $commentMessage = $PMF_LANG['msgWriteNoComment'];
} else {
    $commentMessage = sprintf(
        '%s<a href="#" class="show-comment-form">%s</a>',
        $PMF_LANG['msgYouCan'],
        $PMF_LANG['msgWriteComment']
    );
    $template->parseBlock(
        'mainPageContent',
        'enableComments',
        [
            'numberOfComments' => sprintf(
                '%d %s',
                $numComments[$recordId] ?? 0,
                $PMF_LANG['ad_start_comments']
            ),
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
    $recordId,
    $lang
);

$availableLanguages = $faqConfig->getLanguage()->languageAvailable($faq->faqRecord['id']);

if (!empty($availableLanguages) && count($availableLanguages) > 1) {
    $template->parseBlock(
        'mainPageContent',
        'switchLanguage',
        [
            'msgChangeLanguage' => $PMF_LANG['msgLanguageSubmit'],
        ]
    );
}

if (
    $user->perm->hasPermission($user->getUserId(), 'addtranslation') &&
    !empty($availableLanguages) && count($availableLanguages) > 1
) {
    $template->parseBlock(
        'mainPageContent',
        'addTranslation',
        [
            'msgTranslate' => $PMF_LANG['msgTranslate'],
        ]
    );
}

if ($user->perm->hasPermission($user->getUserId(), 'edit_faq') && !empty($faq->faqRecord['notes'])) {
    $template->parseBlock(
        'mainPageContent',
        'privateNotes',
        [
            'notesHeader' => $PMF_LANG['ad_admin_notes'],
            'notes' => $faq->faqRecord['notes']
        ]
    );
}

if ('-' !== $faqTagging->getAllLinkTagsById($recordId)) {
    $template->parseBlock(
        'mainPageContent',
        'tagsAvailable',
        [
            'renderTags' => $PMF_LANG['msg_tags'] . ': ' . $faqTagging->getAllLinkTagsById($recordId),
        ]
    );
}

if ('' !== $htmlAllCategories) {
    $template->parseBlock(
        'mainPageContent',
        'relatedCategories',
        [
            'renderRelatedCategoriesHeader' => $PMF_LANG['msgArticleCategories'],
            'renderRelatedCategories' => $htmlAllCategories,
        ]
    );
}

if ('' !== $relatedFaqs) {
    $template->parseBlock(
        'mainPageContent',
        'relatedFaqs',
        [
            'renderRelatedArticlesHeader' => $PMF_LANG['msg_related_articles'],
            'renderRelatedArticles' => $relatedFaqs,
        ]
    );
}

$date = new Date($faqConfig);
$captchaHelper = new CaptchaHelper($faqConfig);

// We need some Links from social networks
$faqServices = new Services($faqConfig);
$faqServices->setCategoryId($cat);
$faqServices->setFaqId($id);
$faqServices->setLanguage($lang);
$faqServices->setQuestion($faq->getRecordTitle($id));

// Check if category ID and FAQ ID are linked together
if (!$category->categoryHasLinkToFaq($recordId, $currentCategory)) {
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
        'faqAuthor' => $author,
        'editThisEntry' => $editThisEntry,
        'msgPdf' => $PMF_LANG['msgPDF'],
        'msgPrintFaq' => $PMF_LANG['msgPrintArticle'],
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
        'msgTranslateSubmit' => $PMF_LANG['msgTranslateSubmit'],
        'saveVotingPATH' => sprintf(
            str_replace(
                '%',
                '%%',
                $faqConfig->getDefaultUrl()
            ) . 'index.php?%saction=savevoting',
            $sids
        ),
        'saveVotingID' => $recordId,
        'saveVotingIP' => $_SERVER['REMOTE_ADDR'],
        'msgAverageVote' => $PMF_LANG['msgAverageVote'],
        'renderVotingStars' => '',
        'printVotings' => $faqRating->getVotingResult($recordId),
        'switchLanguage' => $faqHelper->renderChangeLanguageSelector($faq, $currentCategory),
        'msgVoteUsability' => $PMF_LANG['msgVoteUsability'],
        'msgVoteBad' => $PMF_LANG['msgVoteBad'],
        'msgVoteGood' => $PMF_LANG['msgVoteGood'],
        'msgVoteSubmit' => $PMF_LANG['msgVoteSubmit'],
        'writeCommentMsg' => $commentMessage,
        'msgWriteComment' => $PMF_LANG['msgWriteComment'],
        'id' => $recordId,
        'lang' => $lang,
        'msgCommentHeader' => $PMF_LANG['msgCommentHeader'],
        'msgNewContentName' => $PMF_LANG['msgNewContentName'],
        'msgNewContentMail' => $PMF_LANG['msgNewContentMail'],
        'defaultContentMail' => ($user instanceof CurrentUser) ? $user->getUserData('email') : '',
        'defaultContentName' => ($user instanceof CurrentUser) ? $user->getUserData('display_name') : '',
        'msgYourComment' => $PMF_LANG['msgYourComment'],
        'msgNewContentSubmit' => $PMF_LANG['msgNewContentSubmit'],
        'captchaFieldset' => $captchaHelper->renderCaptcha($captcha, 'writecomment', $PMF_LANG['msgCaptcha'], $auth),
        'renderComments' => $faqComment->getComments($recordId, CommentType::FAQ),
        'msg_about_faq' => $PMF_LANG['msg_about_faq'],
    ]
);

$template->parseBlock(
    'index',
    'breadcrumb',
    [
        'breadcrumbHeadline' => $categoryName
    ]
);
