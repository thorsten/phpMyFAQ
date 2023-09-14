<?php

/**
 * Page for adding new questions.
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
 * @since     2002-09-17
 */

use phpMyFAQ\Captcha\Captcha;
use phpMyFAQ\Captcha\Helper\CaptchaHelper;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper as HelperCategory;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

// Check user permissions
if ((-1 === $user->getUserId() && !$faqConfig->get('records.allowQuestionsForGuests'))) {
    $redirect = new RedirectResponse($faqSystem->getSystemUri($faqConfig) . '?action=login');
    $redirect->send();
}

$request = Request::createFromGlobals();
$captcha = Captcha::getInstance($faqConfig);
$captcha->setSessionId($sids);

if ($showCaptcha !== '') {
    $captcha->drawCaptchaImage();
    exit;
}

try {
    $faqSession->userTracking('ask_question', 0);
} catch (Exception) {
    // @todo handle the exception
}

$category->buildCategoryTree();

$categoryId = Filter::filterVar($request->query->get('category_id'), FILTER_VALIDATE_INT, 0);

$categoryHelper = new HelperCategory();
$categoryHelper->setCategory($category);

$captchaHelper = CaptchaHelper::getInstance($faqConfig);

$template->parse(
    'mainPageContent',
    [
        'baseHref' => $faqSystem->getSystemUri($faqConfig),
        'pageHeader' => Translation::get('msgQuestion'),
        'msgQuestion' => Translation::get('msgQuestion'),
        'msgNewQuestion' => Translation::get('msgNewQuestion'),
        'msgMatchingQuestions' => Translation::get('msgMatchingQuestions'),
        'msgFinishSubmission' => Translation::get('msgFinishSubmission'),
        'lang' => $Language->getLanguage(),
        'msgNewContentName' => Translation::get('msgNewContentName'),
        'msgNewContentMail' => Translation::get('msgNewContentMail'),
        'defaultContentMail' => ($user->getUserId() > 0) ? $user->getUserData('email') : '',
        'defaultContentName' =>
            ($user->getUserId() > 0) ? Strings::htmlentities($user->getUserData('display_name')) : '',
        'msgAskCategory' => Translation::get('msgAskCategory'),
        'renderCategoryOptions' => $categoryHelper->renderOptions($categoryId),
        'msgAskYourQuestion' => Translation::get('msgAskYourQuestion'),
        'captchaFieldset' =>
            $captchaHelper->renderCaptcha($captcha, 'ask', Translation::get('msgCaptcha'), $user->isLoggedIn()),
        'msgNewContentSubmit' => Translation::get('msgNewContentSubmit'),
    ]
);
