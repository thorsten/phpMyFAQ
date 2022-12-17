<?php

/**
 * This is the page there a user can add a FAQ record.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2022 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-09-16
 */

use phpMyFAQ\Captcha;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CaptchaHelper;
use phpMyFAQ\Helper\CategoryHelper as HelperCategory;
use phpMyFAQ\Question;
use phpMyFAQ\Strings;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

// Check user permissions
if (-1 === $user->getUserId() && !$faqConfig->get('records.allowNewFaqsForGuests')) {
    header('Location:' . $faqSystem->getSystemUri($faqConfig) . '?action=login');
    exit;
}

// Check permission to add new faqs
if (-1 !== $user->getUserId() && !$user->perm->hasPermission($user->getUserId(), 'addfaq')) {
    header('Location:' . $faqSystem->getSystemUri($faqConfig));
    exit;
}

$captcha = new Captcha($faqConfig);
$captcha->setSessionId($sids);

$questionObject = new Question($faqConfig);

if (!is_null($showCaptcha)) {
    $captcha->drawCaptchaImage();
    exit;
}

try {
    $faqSession->userTracking('new_entry', 0);
} catch (Exception $e) {
    // @todo handle the exception
}

// Get possible user input
$selectedQuestion = Filter::filterInput(INPUT_GET, 'question', FILTER_VALIDATE_INT);
$selectedCategory = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);

$question = $readonly = '';
if (!is_null($selectedQuestion)) {
    $oQuestion = $questionObject->getQuestion($selectedQuestion);
    $question = Strings::htmlentities($oQuestion['question']);
    if (Strings::strlen($question)) {
        $readonly = ' readonly';
    }
}

$category->buildCategoryTree();

$categoryHelper = new HelperCategory();
$categoryHelper->setCategory($category);

$captchaHelper = new CaptchaHelper($faqConfig);

// Enable/Disable WYSIWYG editor
if ($faqConfig->get('main.enableWysiwygEditorFrontend')) {
    $template->parseBlock(
        'mainPageContent',
        'enableWysiwygEditor',
        [
            'currentTimestamp' => $_SERVER['REQUEST_TIME'],
        ]
    );
}

$template->parse(
    'mainPageContent',
    [
        'pageHeader' => $PMF_LANG['msgNewContentHeader'],
        'baseHref' => $faqSystem->getSystemUri($faqConfig),
        'msgNewContentHeader' => $PMF_LANG['msgNewContentHeader'],
        'msgNewContentAddon' => $PMF_LANG['msgNewContentAddon'],
        'lang' => $Language->getLanguage(),
        'openQuestionID' => $selectedQuestion,
        'defaultContentMail' => ($user instanceof CurrentUser) ? $user->getUserData('email') : '',
        'defaultContentName' => ($user instanceof CurrentUser) ? $user->getUserData('display_name') : '',
        'msgNewContentName' => $PMF_LANG['msgNewContentName'],
        'msgNewContentMail' => $PMF_LANG['msgNewContentMail'],
        'msgNewContentCategory' => $PMF_LANG['msgNewContentCategory'],
        'renderCategoryOptions' => $categoryHelper->renderOptions($selectedCategory),
        'msgNewContentTheme' => $PMF_LANG['msgNewContentTheme'],
        'readonly' => $readonly,
        'printQuestion' => $question,
        'msgNewContentArticle' => $PMF_LANG['msgNewContentArticle'],
        'msgNewContentKeywords' => $PMF_LANG['msgNewContentKeywords'],
        'msgNewContentLink' => $PMF_LANG['msgNewContentLink'],
        'captchaFieldset' => $captchaHelper->renderCaptcha($captcha, 'add', $PMF_LANG['msgCaptcha'], $auth),
        'msgNewContentSubmit' => $PMF_LANG['msgNewContentSubmit'],
    ]
);
