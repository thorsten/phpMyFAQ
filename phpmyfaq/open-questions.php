<?php

/**
 * Open questions frontend.
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
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Helper\QuestionHelper;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\TwigWrapper;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = $container->get('phpmyfaq.configuration');
$user = $container->get('phpmyfaq.user.current_user');

$faqSession = $container->get('phpmyfaq.user.session');
$faqSession->setCurrentUser($user);
$faqSession->userTracking('open_questions', 0);

$category = new Category($faqConfig);
$questionHelper = new QuestionHelper();
$questionHelper->setConfiguration($faqConfig)->setCategory($category);

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/');
$twigTemplate = $twig->loadTemplate('./open-questions.twig');

$templateVars = [
    ...$templateVars,
    'title' => sprintf('%s - %s', Translation::get(key: 'msgOpenQuestions'), $faqConfig->getTitle()),
    'metaDescription' => sprintf(Translation::get(key: 'msgOpenQuestionsMetaDesc'), $faqConfig->getTitle()),
    'pageHeader' => Translation::get(key: 'msgOpenQuestions'),
    'msgQuestionText' => Translation::get(key: 'msgQuestionText'),
    'msgDate_User' => Translation::get(key: 'msgDate_User'),
    'msgQuestion2' => Translation::get(key: 'msgQuestion2'),
    'openQuestions' => $questionHelper->getOpenQuestions(),
    'isCloseQuestionEnabled' => $faqConfig->get('records.enableCloseQuestion'),
    'userHasPermissionToAnswer' =>
        $user->perm->hasPermission($user->getUserId(), PermissionType::FAQ_ADD->value)
        || $faqConfig->get('records.allowNewFaqsForGuests'),
    'msgQuestionsWaiting' => Translation::get(key: 'msgQuestionsWaiting'),
    'msgNoQuestionsAvailable' => Translation::get(key: 'msgNoQuestionsAvailable'),
    'msg2answerFAQ' => Translation::get(key: 'msg2answerFAQ'),
    'msg2answer' => Translation::get(key: 'msg2answer'),
    'msgEmailTo' => Translation::get(key: 'msgEmailTo'),
];

return $templateVars;
