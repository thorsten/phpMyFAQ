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
 * @copyright 2002-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-09-17
 */

use phpMyFAQ\Category;
use phpMyFAQ\Enums\Forms\FormIds;
use phpMyFAQ\Filter;
use phpMyFAQ\Forms;
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

$faqConfig = $container->get('phpmyfaq.configuration');
$user = $container->get('phpmyfaq.user.current_user');

$faqSession = $container->get('phpmyfaq.user.session');
$faqSession->setCurrentUser($user);

// Check user permissions
if ((-1 === $user->getUserId() && !$faqConfig->get('records.allowQuestionsForGuests'))) {
    $response = new RedirectResponse($faqConfig->getDefaultUrl() . 'login');
    $response->send();
}

$captcha = $container->get('phpmyfaq.captcha');

$faqSession->userTracking('ask_question', 0);

$category = new Category($faqConfig, $currentGroups);
$category->transform(0);
$category->buildCategoryTree();

$categoryId = Filter::filterVar($request->query->get('category_id'), FILTER_VALIDATE_INT, 0);

$captchaHelper = $container->get('phpmyfaq.captcha.helper.captcha_helper');

$forms = new Forms($faqConfig);
$formData = $forms->getFormData(FormIds::ASK_QUESTION->value);

$categories = $category->getAllCategoryIds();

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/');
$twig->addFilter(new TwigFilter('repeat', fn($string, $times): string => str_repeat((string) $string, $times)));
$twigTemplate = $twig->loadTemplate('./ask.twig');

$templateVars = [
    ... $templateVars,
    'title' => sprintf('%s - %s', Translation::get(key: 'msgQuestion'), $faqConfig->getTitle()),
    'metaDescription' => sprintf(Translation::get(key: 'msgQuestionMetaDesc'), $faqConfig->getTitle()),
    'msgMatchingQuestions' => Translation::get(key: 'msgMatchingQuestions'),
    'msgFinishSubmission' => Translation::get(key: 'msgFinishSubmission'),
    'lang' => $Language->getLanguage(),
    'defaultContentMail' => ($user->getUserId() > 0) ? $user->getUserData('email') : '',
    'defaultContentName' => ($user->getUserId() > 0) ? $user->getUserData('display_name') : '',
    'selectedCategory' => $categoryId,
    'categories' => $category->getCategoryTree(),
    'captchaFieldset' =>
        $captchaHelper->renderCaptcha($captcha, 'ask', Translation::get(key: 'msgCaptcha'), $user->isLoggedIn()),
    'msgNewContentSubmit' => Translation::get(key: 'msgNewContentSubmit'),
    'noCategories' => $categories === [],
    'msgFormDisabledDueToMissingCategories' => Translation::get(key: 'msgFormDisabledDueToMissingCategories')
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

return $templateVars;
