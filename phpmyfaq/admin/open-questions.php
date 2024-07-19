<?php

/**
 * Delete open questions.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2024 phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2003-02-24
 */

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Date;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Question;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\CategoryNameTwigExtension;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Twig\Extra\Intl\IntlExtension;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);

if ($user->perm->hasPermission($user->getUserId(), PermissionType::QUESTION_DELETE->value)) {
    $category = new Category($faqConfig, [], false);
    $question = new Question($faqConfig);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $date = new Date($faqConfig);

    $questionId = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);

    if ($csrfToken && Token::getInstance()->verifyToken('toggle-question-visibility', $csrfToken)) {
        $csrfChecked = true;
    } else {
        $csrfChecked = false;
    }

    $toggle = Filter::filterInput(INPUT_GET, 'is_visible', FILTER_SANITIZE_SPECIAL_CHARS);
    if ($csrfChecked && $toggle === 'toggle') {
        $isVisible = $question->getVisibility($questionId);
        $question->setVisibility($questionId, ($isVisible == 'N' ? 'Y' : 'N'));
    }

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $twig->addExtension(new IntlExtension());
    $twig->addExtension(new CategoryNameTwigExtension());
    $template = $twig->loadTemplate('./admin/content/open-questions.twig');

    $openQuestions = $question->getAll();

    $templateVars = [
        'msgOpenQuestions' => Translation::get('msgOpenQuestions'),
        'csrfTokenDeleteQuestion' => Token::getInstance()->getTokenString('delete-questions'),
        'currentLocale' => $faqConfig->getLanguage()->getLanguage(),
        'msgAuthor' => Translation::get('ad_entry_author'),
        'msgQuestion' => Translation::get('ad_entry_theme'),
        'msgVisibility' => Translation::get('ad_entry_visibility'),
        'questions' => $openQuestions,
        'yes' => Translation::get('ad_gen_yes'),
        'no' => Translation::get('ad_gen_no'),
        'enableCloseQuestion' => $faqConfig->get('records.enableCloseQuestion'),
        'msg2answerFAQ' => Translation::get('msg2answerFAQ'),
        'msgTakeQuestion' => Translation::get('ad_ques_take'),
        'csrfTokenToggleVisibility' => Token::getInstance()->getTokenString('toggle-question-visibility'),
        'msgDeleteAllOpenQuestions' => Translation::get('msgDelete'),
    ];

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}
