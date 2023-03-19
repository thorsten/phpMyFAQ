<?php

/**
 * This is the page there a user can add a FAQ record.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-09-16
 */

use phpMyFAQ\Captcha\Captcha;
use phpMyFAQ\Captcha\Helper\CaptchaHelper;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper as HelperCategory;
use phpMyFAQ\Question;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
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

$captcha = Captcha::getInstance($faqConfig);
$captcha->setSessionId($sids);

$questionObject = new Question($faqConfig);

if (!is_null($showCaptcha)) {
    $captcha->drawCaptchaImage();
    exit;
}

try {
    $faqSession->userTracking('new_entry', 0);
} catch (Exception $exception) {
    $faqConfig->getLogger()->error('Tracking of new entry', ['exception' => $exception->getMessage()]);
}

// Get possible user input
$selectedQuestion = Filter::filterInput(INPUT_GET, 'question', FILTER_VALIDATE_INT);
$selectedCategory = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, -1);

$question = $readonly = '';
if (!is_null($selectedQuestion)) {
    $questionData = $questionObject->getQuestion($selectedQuestion);
    $question = Strings::htmlentities($questionData['question']);
    if (Strings::strlen($question)) {
        $readonly = ' readonly';
    }
}

$category->buildCategoryTree();

$categoryHelper = new HelperCategory();
$categoryHelper->setCategory($category);

$captchaHelper = CaptchaHelper::getInstance($faqConfig);

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
        'pageHeader' => Translation::get('msgNewContentHeader'),
        'baseHref' => $faqSystem->getSystemUri($faqConfig),
        'msgNewContentHeader' => Translation::get('msgNewContentHeader'),
        'msgNewContentAddon' => Translation::get('msgNewContentAddon'),
        'lang' => $Language->getLanguage(),
        'openQuestionID' => $selectedQuestion,
        'defaultContentMail' => ($user->getUserId() > 0) ? $user->getUserData('email') : '',
        'defaultContentName' => ($user->getUserId() > 0) ? $user->getUserData('display_name') : '',
        'msgNewContentName' => Translation::get('msgNewContentName'),
        'msgNewContentMail' => Translation::get('msgNewContentMail'),
        'msgNewContentCategory' => Translation::get('msgNewContentCategory'),
        'renderCategoryOptions' => $categoryHelper->renderOptions($selectedCategory),
        'msgNewContentTheme' => Translation::get('msgNewContentTheme'),
        'readonly' => $readonly,
        'printQuestion' => $question,
        'msgNewContentArticle' => Translation::get('msgNewContentArticle'),
        'msgNewContentKeywords' => Translation::get('msgNewContentKeywords'),
        'msgNewContentLink' => Translation::get('msgNewContentLink'),
        'captchaFieldset' =>
            $captchaHelper->renderCaptcha($captcha, 'add', Translation::get('msgCaptcha'), $user->isLoggedIn()),
        'msgNewContentSubmit' => Translation::get('msgNewContentSubmit'),
    ]
);
