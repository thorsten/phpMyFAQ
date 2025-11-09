<?php

/**
 * This is the page where a user can add a FAQ record.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-09-16
 */

use phpMyFAQ\Category;
use phpMyFAQ\Enums\Forms\FormIds;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Forms;
use phpMyFAQ\Question;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Twig\TwigWrapper;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Twig\TwigFilter;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$request = Request::createFromGlobals();
$faqSystem = new System();

$faqConfig = $container->get('phpmyfaq.configuration');
$user = $container->get('phpmyfaq.user.current_user');

$faqSession = $container->get('phpmyfaq.user.session');
$faqSession->setCurrentUser($user);

// Check user permissions
if (-1 === $user->getUserId() && !$faqConfig->get('records.allowNewFaqsForGuests')) {
    $response = new RedirectResponse($faqSystem->getSystemUri($faqConfig) . 'login');
    $response->send();
}

// Check permission to add new faqs
if (-1 !== $user->getUserId() && !$user->perm->hasPermission($user->getUserId(), PermissionType::FAQ_ADD->value)) {
    $response = new RedirectResponse($faqSystem->getSystemUri($faqConfig));
    $response->send();
}

$captcha = $container->get('phpmyfaq.captcha');

$questionObject = new Question($faqConfig);

$faqSession->userTracking('new_entry', 0);

// Get possible user input
$selectedQuestion = Filter::filterVar($request->query->get('question'), FILTER_VALIDATE_INT);
$selectedCategory = Filter::filterVar($request->query->get('cat'), FILTER_VALIDATE_INT, -1);
$question = '';
$readonly = '';
$displayFullForm = false;
if (!is_null($selectedQuestion)) {
    $questionData = $questionObject->get($selectedQuestion);
    $question = $questionData['question'];
    if (Strings::strlen($question) !== 0) {
        $readonly = ' readonly';
    }

    // Display full form even if the user switched off single fields because of use together with answering open
    // questions
    $displayFullForm = true;
}

$category = new Category($faqConfig, $currentGroups);
$category->transform(0);
$category->buildCategoryTree();

$captchaHelper = $container->get('phpmyfaq.captcha.helper.captcha_helper');

$forms = new Forms($faqConfig);
$formData = $forms->getFormData(FormIds::ADD_NEW_FAQ->value);

$categories = $category->getAllCategoryIds();

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/');
$twig->addFilter(new TwigFilter('repeat', fn($string, $times): string => str_repeat((string) $string, $times)));
$twigTemplate = $twig->loadTemplate('./add.twig');

// Twig template variables
$templateVars = [
    ... $templateVars,
    'title' => sprintf('%s - %s', Translation::get(languageKey: 'msgAddContent'), $faqConfig->getTitle()),
    'metaDescription' => sprintf('%s | %s', Translation::get(languageKey: 'msgNewContentHeader'), $faqConfig->getTitle()),
    'msgNewContentHeader' => Translation::get(languageKey: 'msgNewContentHeader'),
    'msgNewContentAddon' => Translation::get(languageKey: 'msgNewContentAddon'),
    'lang' => $Language->getLanguage(),
    'openQuestionID' => $selectedQuestion,
    'defaultContentMail' => ($user->getUserId() > 0) ? $user->getUserData('email') : '',
    'defaultContentName' => ($user->getUserId() > 0) ? $user->getUserData('display_name') : '',
    'msgNewContentName' => Translation::get(languageKey: 'msgNewContentName'),
    'msgNewContentMail' => Translation::get(languageKey: 'msgNewContentMail'),
    'msgNewContentCategory' => Translation::get(languageKey: 'msgNewContentCategory'),
    'selectedCategory' => $selectedCategory,
    'categories' => $category->getCategoryTree(),
    'msgNewContentTheme' => Translation::get(languageKey: 'msgNewContentTheme'),
    'readonly' => $readonly,
    'printQuestion' => $question,
    'msgNewContentArticle' => Translation::get(languageKey: 'msgNewContentArticle'),
    'msgNewContentKeywords' => Translation::get(languageKey: 'msgNewContentKeywords'),
    'msgNewContentLink' => Translation::get(languageKey: 'msgNewContentLink'),
    'captchaFieldset' =>
        $captchaHelper->renderCaptcha($captcha, 'add', Translation::get(languageKey: 'msgCaptcha'), $user->isLoggedIn()),
    'msgNewContentSubmit' => Translation::get(languageKey: 'msgNewContentSubmit'),
    'enableWysiwygEditor' => $faqConfig->get('main.enableWysiwygEditorFrontend'),
    'currentTimestamp' => $request->server->get('REQUEST_TIME'),
    'msgSeparateKeywordsWithCommas' => Translation::get(languageKey: 'msgSeparateKeywordsWithCommas'),
    'noCategories' => $categories === [],
    'msgFormDisabledDueToMissingCategories' => Translation::get(languageKey: 'msgFormDisabledDueToMissingCategories'),
    'displayFullForm' => $displayFullForm,
];

// Collect data for displaying form
foreach ($formData as $input) {
    $active = sprintf('id%d_active', (int)$input->input_id);
    $label = sprintf('id%d_label', (int)$input->input_id);
    $required = sprintf('id%d_required', (int)$input->input_id);
    $templateVars = [
        ...$templateVars,
        $active => (bool)$input->input_active,
        $label => $input->input_label,
        $required => ((int)$input->input_required !== 0) ? 'required' : ''
    ];
}

return $templateVars;
