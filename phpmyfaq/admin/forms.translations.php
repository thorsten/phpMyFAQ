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
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Language\LanguageCodes;
use Twig\TwigFilter;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
$user = CurrentUser::getCurrentUser($faqConfig);

if ($user->perm->hasPermission($user->getUserId(), PermissionType::FORMS_EDIT->value)) {
    $forms = new Forms($faqConfig);

    $formId = Filter::filterInput(INPUT_GET, 'formid', FILTER_SANITIZE_NUMBER_INT);
    $inputId = Filter::filterInput(INPUT_GET, 'inputid', FILTER_SANITIZE_NUMBER_INT);

    // Get supported languages for adding new translations
    $languages = [];
    foreach (LanguageCodes::getAllSupported() as $code => $language) {
        if (!in_array($code, $forms->getTranslatedLanguages($formId, $inputId))) {
            $languages[] = $language;
        }
    }

    // Twig filter for language codes
    // Not seperated as TwigExtension because of special function and handling of 'default' value in this context
    $filter = new TwigFilter('languageCode', function ($string) {
        if ($string === 'default') {
            return $string;
        } else {
            return LanguageCodes::get($string);
        }
    });

    $templateVars = [
        'translations' => $forms->getTranslations($formId, $inputId),
        'ad_entry_locale' => Translation::get('ad_entry_locale'),
        'msgInputLabel' => Translation::get('msgInputLabel'),
        'ad_sess_pageviews' => Translation::get('ad_sess_pageviews'),
        'msgFormsEditTranslations' => Translation::get('msgFormsEditTranslations'),
        'csrfTokenEditTranslation' => Token::getInstance()->getTokenString('edit-translation'),
        'csrfTokenDeleteTranslation' => Token::getInstance()->getTokenString('delete-translation'),
        'languages' => $languages,
        'msgSelectLanguage' => Translation::get('msgSelectLanguage'),
        'msgTranslationText' => Translation::get('msgTranslationText'),
        'msgAddTranslation' => Translation::get('msgAddTranslation'),
        'csrfTokenAddTranslation' => Token::getInstance()->getTokenString('add-translation'),
        'formId' => $formId,
        'inputId' => $inputId,
        'msgEditForms' => Translation::get('msgEditForms')
    ];

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $twig->addFilter($filter);
    $template = $twig->loadTemplate('@admin/configuration/forms.translations.twig');

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}
