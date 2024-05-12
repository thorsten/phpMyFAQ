<?php

/**
 * The main stop words configuration frontend.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-01
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$template = $twig->loadTemplate('./admin/configuration/stopwords.twig');

$sortedLanguageCodes = [];
if ($user->perm->hasPermission($user->getUserId(), PermissionType::CONFIGURATION_EDIT->value)) {
    $sortedLanguageCodes = LanguageCodes::getAll();
    asort($sortedLanguageCodes);
    reset($sortedLanguageCodes);
}

$templateVars = [
    'adminHeaderStopWords' => Translation::get('ad_menu_stopwordsconfig'),
    'hasPermission' => $user->perm->hasPermission($user->getUserId(), PermissionType::CONFIGURATION_EDIT),
    'msgDescription' => Translation::get('ad_stopwords_desc'),
    'csrfToken' => Token::getInstance()->getTokenInput('stopwords'),
    'msgStopWordsLabel' => Translation::get('ad_stopwords_desc'),
    'sortedLanguageCodes' => $sortedLanguageCodes,
    'buttonAdd' => Translation::get('ad_config_stopword_input'),
];

echo $template->render($templateVars);

if (!$user->perm->hasPermission($user->getUserId(), PermissionType::CONFIGURATION_EDIT->value)) {
    require __DIR__ . '/no-permission.php';
}
