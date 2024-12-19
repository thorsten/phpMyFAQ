<?php

/**
 * Edit forms
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <modelrailroader@gmx-topmail.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-09
 */

use phpMyFAQ\Forms;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Enums\Forms\FormIds;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Enums\PermissionType;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
$user = CurrentUser::getCurrentUser($faqConfig);

if ($user->perm->hasPermission($user->getUserId(), PermissionType::FORMS_EDIT->value)) {
    $forms = new Forms($faqConfig);
    $translation = new Translation();

    $templateVars = [
        'msgEditForms' => Translation::get('msgEditForms'),
        'formDataAskQuestion' => $forms->getFormData(FormIds::ASK_QUESTION->value),
        'formDataAddContent' => $forms->getFormData(FormIds::ADD_NEW_FAQ->value),
        'msgQuestion' => Translation::get('msgQuestion'),
        'msgAddContent' => Translation::get('msgAddContent'),
        'csrfActivate' => Token::getInstance()->getTokenString('activate-input'),
        'csrfRequired' => Token::getInstance()->getTokenString('require-input'),
        'ad_entry_id' => Translation::get('ad_entry_id'),
        'msgInputLabel' => Translation::get('msgInputLabel'),
        'msgInputType' => Translation::get('msgInputType'),
        'ad_entry_active' => Translation::get('ad_entry_active'),
        'msgRequiredInputField' => Translation::get('msgRequiredInputField'),
        'msgFormsEditTranslations' => Translation::get('msgFormsEditTranslations'),
        'ad_categ_translate' => Translation::get('ad_categ_translate'),
        'msgHintDeactivateForms' => Translation::get('msgHintDeactivateForms')
    ];

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $template = $twig->loadTemplate('@admin/configuration/forms.twig');

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}
