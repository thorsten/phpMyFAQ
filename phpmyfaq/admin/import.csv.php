<?php

/**
 * Frontend for importing records from a csv file.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-24
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\PermissionType;
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

if ($user->perm->hasPermission($user->getUserId(), PermissionType::FAQ_ADD->value)) {
    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $template = $twig->loadTemplate('@admin/import-export/import.csv.twig');

    $templateVars = [
        'adminHeaderImport' => Translation::get('msgImportRecords'),
        'adminHeaderCSVImport' => Translation::get('msgImportCSVFile'),
        'adminBodyCSVImport' => Translation::get('msgImportCSVFileBody'),
        'adminImportLabel' => Translation::get('ad_csv_file'),
        'adminCSVImport' => Translation::get('msgImport'),
        'adminHeaderCSVImportColumns' => Translation::get('msgColumnStructure'),
        'categoryId' => Translation::get('ad_categ_categ'),
        'question' => Translation::get('ad_entry_topic'),
        'answer' => Translation::get('ad_entry_content'),
        'keywords' => Translation::get('ad_entry_keywords'),
        'author' => Translation::get('ad_entry_author'),
        'email' => Translation::get('msgEmail'),
        'languageCode' => Translation::get('msgLanguageCode'),
        'seperateWithCommas' => Translation::get('msgSeperateWithCommas'),
        'tags' => Translation::get('ad_entry_tags'),
        'msgImportRecordsColumnStructure' => Translation::get('msgImportRecordsColumnStructure'),
        'csrfToken' => Token::getInstance($container->get('session'))->getTokenString('importfaqs'),
        'is_active' => Translation::get('ad_entry_active'),
        'is_sticky' => Translation::get('msgStickyFAQ'),
        'trueFalse' => Translation::get('msgCSVImportTrueOrFalse')
    ];

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}
