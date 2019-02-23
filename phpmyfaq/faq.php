<?php

/**
 * Shows the page with the FAQ record and - when available - the user comments.
 *
 * 
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Lars Tiedemann <larstiedemann@yahoo.de>
 * @copyright 2002-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2002-08-27
 */

use phpMyFAQ\Attachment\Factory;
use phpMyFAQ\Captcha;
use phpMyFAQ\Comment;
use phpMyFAQ\Date;
use phpMyFAQ\Filter;
use phpMyFAQ\Glossary;
use phpMyFAQ\Helper\CaptchaHelper;
use phpMyFAQ\Helper\FaqHelper as HelperFaq;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Link;
use phpMyFAQ\Linkverifier;
use phpMyFAQ\Rating;
use phpMyFAQ\Relation;
use phpMyFAQ\Strings;
use phpMyFAQ\Tags;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Utils;
use phpMyFAQ\Visits;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha = new Captcha($faqConfig);
$oGlossary = new Glossary($faqConfig);
$faqTagging = new Tags($faqConfig);
$faqRelation = new Relation($faqConfig);
$faqRating = new Rating($faqConfig);
$faqComment = new Comment($faqConfig);
$markDown = new \ParsedownExtra();
$faqHelper = new HelperFaq($faqConfig);

if (is_null($user)) {
    $user = new CurrentUser($faqConfig);
}

$faqSearchResult = new SearchResultset($user, $faq, $faqConfig);

$captcha->setSessionId($sids);
if (!is_null($showCaptcha)) {
    $captcha->showCaptchaImg();
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
$recordId = $faq->faqRecord['id'];

try {
    $faqSession->userTracking('article_view', $recordId);
} catch (Exception $e) {
    // @todo handle the exception
}

$faqVisits = new Visits($faqConfig);
$faqVisits->logViews($recordId);

// Add Glossary entries for answers only
$question = $faq->getRecordTitle($recordId);
if ($faqConfig->get('main.enableMarkdownEditor')) {
    $answer = $markDown->text($faq->faqRecord['content']);
} else {
    $answer = $faqHelper->renderMarkupContent($faq->faqRecord['content']);
}
$answer = $oGlossary->insertItemsIntoContent($answer);

// Set the path of the current category
$categoryName = $category->getPath($currentCategory, ' &raquo; ', true);

$highlight = Filter::filterInput(INPUT_GET, 'highlight', FILTER_SANITIZE_STRIPPED);
if (!is_null($highlight) && $highlight != '/' && $highlight != '<' && $highlight != '>' && Strings::strlen($highlight) > 3) {
    $highlight = str_replace("'", '´', $highlight);
    $highlight = str_replace(array('^', '.', '?', '*', '+', '{', '}', '(', ')', '[', ']'), '', $highlight);
    $highlight = preg_quote($highlight, '/');
    $searchItems = explode(' ', $highlight);

    foreach ($searchItems as $item) {
        if (Strings::strlen($item) > 2) {
            $question = Utils::setHighlightedString($question, $item);
            $answer = Utils::setHighlightedString($answer, $item);
        }
    }
}

$linkVerifier = new Linkverifier($faqConfig);
$linkArray = $linkVerifier->getUrlpool();
if (isset($linkArray['href'])) {
    foreach (array_unique($linkArray['href']) as $_url) {
        $xpos = strpos($_url, 'index.php?action=faq');
        if (!($xpos === false)) {
            // Get the FaqHelper link title
            $matches = array();
            preg_match('/id=([\d]+)/ism', $_url, $matches);
            $_id = $matches[1];
            $_title = $faq->getRecordTitle($_id);
            $_link = substr($_url, $xpos + 9);
            if (strpos($_url, '&amp;') === false) {
                $_link = str_replace('&', '&amp;', $_link);
            }
            $oLink = new Link(Link::getSystemRelativeUri().$_link, $faqConfig);
            $oLink->itemTitle = $oLink->tooltip = $_title;
            $newFaqPath = $oLink->toString();
            $answer = str_replace($_url, $newFaqPath, $answer);
        }
    }
}

// List all faq attachments
if ($faqConfig->get('records.disableAttachments') && 'yes' == $faq->faqRecord['active']) {
    $attList = Factory::fetchByRecordId($faqConfig, $recordId);
    $outstr = '';

    foreach ($attList as $att) {
        $outstr .= sprintf('<a href="%s">%s</a>, ',
            $att->buildUrl(),
            $att->getFilename());
    }
    if (count($attList) > 0) {
        $answer .= '<p>'.$PMF_LANG['msgAttachedFiles'].' '.Strings::substr($outstr, 0, -2).'</p>';
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
$faqSearchResult->reviewResultset(
    $faqRelation->getAllRelatedById(
        $recordId,
        $faq->faqRecord['title'],
        $faq->faqRecord['keywords']
    )
);

$searchHelper = new SearchHelper($faqConfig);
$relatedFaqs = $searchHelper->renderRelatedFaqs($faqSearchResult, $recordId);

// Show link to edit the faq?
$editThisEntry = '';
if ($user->perm->checkRight($user->getUserId(), 'edit_faq')) {
    $editThisEntry = sprintf(
        '<i aria-hidden="true" class="fas fa-pencil"></i> <a class="data" href="%sadmin/index.php?action=editentry&id=%d&lang=%s">%s</a>',
        Link::getSystemRelativeUri('index.php'),
        $recordId,
        $lang,
        $PMF_LANG['ad_entry_edit_1'].' '.$PMF_LANG['ad_entry_edit_2']
    );
}

// Is the faq expired?
$expired = (date('YmdHis') > $faq->faqRecord['dateEnd']);

// Does the user have the right to add a comment?
if ((-1 === $user->getUserId() && !$faqConfig->get('records.allowCommentsForGuests')) ||
    ($faq->faqRecord['active'] === 'no') || ('n' === $faq->faqRecord['comment']) || $expired) {
    $commentMessage = $PMF_LANG['msgWriteNoComment'];
} else {
    $commentMessage = sprintf(
        '%s<a href="#" class="show-comment-form">%s</a>',
        $PMF_LANG['msgYouCan'],
        $PMF_LANG['msgWriteComment']
    );
}

$translationUrl = sprintf(
    str_replace(
        '%',
        '%%',
        Link::getSystemRelativeUri('index.php')
    ).'index.php?%saction=translate&amp;cat=%s&amp;id=%d&amp;srclang=%s',
    $sids,
    $currentCategory,
    $recordId,
    $lang
);

$availableLanguages = $faqConfig->getLanguage()->languageAvailable($faq->faqRecord['id']);

if (!empty($availableLanguages) && count($availableLanguages) > 1) {
    $template->parseBlock(
        'writeContent',
        'switchLanguage',
        [
            'msgChangeLanguage' => $PMF_LANG['msgLanguageSubmit'],
        ]
    );
}

if ($user->perm->checkRight($user->getUserId(), 'addtranslation') &&
    !empty($availableLanguages) && count($availableLanguages) > 1) {
    $template->parseBlock(
        'writeContent',
        'addTranslation',
        [
            'msgTranslate' => $PMF_LANG['msgTranslate'],
        ]
    );
}

if ($user->perm->checkRight($user->getUserId(), 'edit_faq') && !empty($faq->faqRecord['notes'])) {
    $template->parseBlock(
        'writeContent',
        'privateNotes',
        [
            'notesHeader' => $PMF_LANG['ad_admin_notes'],
            'notes' => $faq->faqRecord['notes']
        ]
    );
}

if ('-' !== $faqTagging->getAllLinkTagsById($recordId)) {
    $template->parseBlock(
        'writeContent',
        'tagsAvailable',
        [
            'renderTags' => $PMF_LANG['msg_tags'].': '.$faqTagging->getAllLinkTagsById($recordId),
        ]
    );
}

if ('' !== $htmlAllCategories) {
    $template->parseBlock(
        'writeContent',
        'relatedCategories',
        [
            'renderRelatedCategoriesHeader' => $PMF_LANG['msgArticleCategories'],
            'renderRelatedCategories' => $htmlAllCategories,
        ]
    );
}

if ('' !== $relatedFaqs) {
    $template->parseBlock(
        'writeContent',
        'relatedFaqs',
        [
            'renderRelatedArticlesHeader' => $PMF_LANG['msg_related_articles'],
            'renderRelatedArticles' => $relatedFaqs,
        ]
    );
}

$date = new Date($faqConfig);
$captchaHelper = new CaptchaHelper($faqConfig);

$numComments = $faqComment->getNumberOfComments();

// Check if category ID and FAQ ID are linked together
$isLinkedFAQ = $category->categoryHasLinkToFaq($recordId, $currentCategory);
if (!$isLinkedFAQ) {
    $http->sendStatus(404);
}

$template->parse(
    'writeContent',
    array(
        'baseHref' => $faqSystem->getSystemUri($faqConfig),
        'writeRubrik' => $categoryName,
        'solution_id' => $faq->faqRecord['solution_id'],
        'solution_id_link' => Link::getSystemRelativeUri().'?solution_id='.$faq->faqRecord['solution_id'],
        'writeThema' => $question,
        'writeContent' => $answer,
        'writeDateMsg' => $date->format($faq->faqRecord['date']),
        'writeAuthor' => $faq->faqRecord['author'],
        'numberOfComments' => sprintf(
            '%d %s',
            isset($numComments[$recordId]) ? $numComments[$recordId] : 0,
            $PMF_LANG['ad_start_comments']
        ),
        'editThisEntry' => $editThisEntry,
        'translationUrl' => $translationUrl,
        'languageSelection' => Language::selectLanguages($LANGCODE, false, $availableLanguages, 'translation'),
        'msgTranslateSubmit' => $PMF_LANG['msgTranslateSubmit'],
        'saveVotingPATH' => sprintf(
            str_replace(
                '%',
                '%%',
                Link::getSystemRelativeUri('index.php')
            ).'index.php?%saction=savevoting',
            $sids
        ),
        'saveVotingID' => $recordId,
        'saveVotingIP' => $_SERVER['REMOTE_ADDR'],
        'msgAverageVote' => $PMF_LANG['msgAverageVote'],
        'renderVotingStars' => '',
        'printVotings' => $faqRating->getVotingResult($recordId),
        'switchLanguage' => $faqHelper->renderChangeLanguageSelector($faq, $currentCategory),
        'msgVoteUseability' => $PMF_LANG['msgVoteUseability'],
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
        'writeComments' => $faqComment->getComments($recordId, Comment::COMMENT_TYPE_FAQ),
        'msg_about_faq' => $PMF_LANG['msg_about_faq'],
    )
);

$template->parseBlock(
    'index',
    'breadcrumb',
    [
        'breadcrumbHeadline' => $categoryName
    ]
);
