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
 * @copyright 2002-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-09-17
 */

use phpMyFAQ\Category;
use phpMyFAQ\Helper\QuestionHelper;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = $container->get('phpmyfaq.configuration');
$user = $container->get('phpmyfaq.user.current_user');

$faqSession = $container->get('phpmyfaq.session');
$faqSession->setCurrentUser($user);
$faqSession->userTracking('open_questions', 0);

$category = new Category($faqConfig);
$questionHelper = new QuestionHelper();
$questionHelper
    ->setConfiguration($faqConfig)
    ->setCategory($category);

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/');
$twigTemplate = $twig->loadTemplate('./open-questions.twig');

$templateVars = [
    ... $templateVars,
    'title' => sprintf('%s - %s', Translation::get('msgOpenQuestions'), $faqConfig->getTitle()),
    'metaDescription' => sprintf(Translation::get('msgOpenQuestionsMetaDesc'), $faqConfig->getTitle()),
    'pageHeader' => Translation::get('msgOpenQuestions'),
    'msgQuestionText' => Translation::get('msgQuestionText'),
    'msgDate_User' => Translation::get('msgDate_User'),
    'msgQuestion2' => Translation::get('msgQuestion2'),
    'openQuestions' => $questionHelper->getOpenQuestions(),
    'isCloseQuestionEnabled' => $faqConfig->get('records.enableCloseQuestion'),
    'msgQuestionsWaiting' => Translation::get('msgQuestionsWaiting'),
    'msgNoQuestionsAvailable' => Translation::get('msgNoQuestionsAvailable'),
    'msg2answerFAQ' => Translation::get('msg2answerFAQ'),
    'msg2answer' => Translation::get('msg2answer')
];

return $templateVars;
