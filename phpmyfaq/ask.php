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
 * @copyright 2002-2024 phpMyFAQ Team
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
use phpMyFAQ\Forms;
use phpMyFAQ\Enums\Forms\FormIds;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

// Check user permissions
if ((-1 === $user->getUserId() && !$faqConfig->get('records.allowQuestionsForGuests'))) {
    $response = new RedirectResponse($faqSystem->getSystemUri($faqConfig) . 'login');
    $response->send();
}

$request = Request::createFromGlobals();
$captcha = Captcha::getInstance($faqConfig);
$captcha->setSessionId($sids);

$faqSession->userTracking('ask_question', 0);

$category->buildCategoryTree();

$categoryId = Filter::filterVar($request->query->get('category_id'), FILTER_VALIDATE_INT, 0);

$categoryHelper = new HelperCategory();
$categoryHelper->setCategory($category);

$captchaHelper = CaptchaHelper::getInstance($faqConfig);

$forms = new Forms($faqConfig);
$formData = $forms->getFormData(FormIds::ASK_QUESTION->value);

$categories = $category->getAllCategoryIds();

$templateVars = [
    'baseHref' => $faqSystem->getSystemUri($faqConfig),
    'msgMatchingQuestions' => Translation::get('msgMatchingQuestions'),
    'msgFinishSubmission' => Translation::get('msgFinishSubmission'),
    'lang' => $Language->getLanguage(),
    'defaultContentMail' => ($user->getUserId() > 0) ? $user->getUserData('email') : '',
    'defaultContentName' =>
        ($user->getUserId() > 0) ? Strings::htmlentities($user->getUserData('display_name')) : '',
    'renderCategoryOptions' => $categoryHelper->renderOptions($categoryId),
    'captchaFieldset' =>
        $captchaHelper->renderCaptcha($captcha, 'ask', Translation::get('msgCaptcha'), $user->isLoggedIn()),
    'msgNewContentSubmit' => Translation::get('msgNewContentSubmit'),
    'noCategories' => empty($categories),
    'msgFormDisabledDueToMissingCategories' => Translation::get('msgFormDisabledDueToMissingCategories')
];

// Collect data for displaying form
foreach ($formData as $input) {
    if ((int)$input->input_active !== 0) {
        $label = sprintf('id%d_label', (int)$input->input_id);
        $required = sprintf('id%d_required', (int)$input->input_id);
        $templateVars = [
            ...$templateVars,
            $label => $input->input_label,
            $required => ((int)$input->input_required !== 0) ? 'required' : ''
        ];
    }
}

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
        'defaultContentMail' => ($user->getUserId() > 0) ? Strings::htmlentities($user->getUserData('email')) : '',
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
