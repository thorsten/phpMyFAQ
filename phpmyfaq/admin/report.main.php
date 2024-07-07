<?php

/**
 * The reporting page.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2011-01-12
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

if ($user->perm->hasPermission($user->getUserId(), PermissionType::REPORTS->value)) {
    $templateVars = [
        'ad_menu_reports' => Translation::get('ad_menu_reports'),
        'csrfTokenInput' => Token::getInstance()->getTokenInput('create-report'),
        'ad_stat_report_make_report' => Translation::get('ad_stat_report_make_report'),
        'ad_stat_report_fields' => Translation::get('ad_stat_report_fields'),
        'ad_stat_report_category' => Translation::get('ad_stat_report_category'),
        'ad_stat_report_sub_category' => Translation::get('ad_stat_report_sub_category'),
        'ad_stat_report_translations' => Translation::get('ad_stat_report_translations'),
        'ad_stat_report_language' => Translation::get('ad_stat_report_language'),
        'ad_stat_report_id' => Translation::get('ad_stat_report_id'),
        'ad_stat_report_sticky' => Translation::get('ad_stat_report_sticky'),
        'ad_stat_report_title' => Translation::get('ad_stat_report_title'),
        'ad_stat_report_creation_date' => Translation::get('ad_stat_report_creation_date'),
        'ad_stat_report_owner' => Translation::get('ad_stat_report_owner'),
        'ad_stat_report_last_modified_person' => Translation::get('ad_stat_report_last_modified_person'),
        'ad_stat_report_url' => Translation::get('ad_stat_report_url'),
        'ad_stat_report_visits' => Translation::get('ad_stat_report_visits')
    ];

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $template = $twig->loadTemplate('./admin/statistics/report.main.twig');

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}
