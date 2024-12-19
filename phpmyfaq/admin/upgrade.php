<?php

/**
 * The upgrade administration view
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-01
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Enums\ReleaseType;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$request = Request::createFromGlobals();
$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$template = $twig->loadTemplate('@admin/configuration/upgrade.twig');

if (
    $faqConfig->get('upgrade.onlineUpdateEnabled') &&
    $user->perm->hasPermission($user->getUserId(), PermissionType::CONFIGURATION_EDIT->value)
) {
    $templateVars = [
        'adminHeaderUpgrade' => Translation::get('ad_menu_upgrade'),
        'headerCheckHealth' => Translation::get('headerCheckHealth'),
        'msgHealthCheck' => Translation::get('msgHealthCheck'),
        'buttonCheckHealth' => Translation::get('buttonCheckHealth'),
        'headerCheckUpdates' => Translation::get('headerCheckUpdates'),
        'msgUpdateCheck' => Translation::get('msgUpdateCheck'),
        'buttonCheckUpdates' => Translation::get('buttonCheckUpdates'),
        'headerExtractPackage' => Translation::get('headerExtractPackage'),
        'msgExtractPackage' => Translation::get('msgExtractPackage'),
        'buttonExtractPackage' => Translation::get('buttonExtractPackage'),
        'headerInstallDownloadedPackage' => Translation::get('headerInstallDownloadedPackage'),
        'msgExtractToFileSystem' => Translation::get('msgExtractToFileSystem'),
        'msgInstallDownloadedPackage' => Translation::get('msgInstallDownloadedPackage'),
        'isOnNightlies' => $faqConfig->get('upgrade.releaseEnvironment') === ReleaseType::NIGHTLY->value,
        'releaseEnvironment' => ucfirst((string) $faqConfig->get('upgrade.releaseEnvironment')),
        'dateLastChecked' => $faqConfig->get('upgrade.dateLastChecked'),
        'versionCurrent' => $faqConfig->get('main.currentVersion'),
    ];

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}
