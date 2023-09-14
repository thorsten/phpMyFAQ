<?php

/**
 * Frontend for Backup and Restore.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-24
 */

use phpMyFAQ\Configuration;
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

if ($user->perm->hasPermission($user->getUserId(), 'backup')) {
    $twig = new TwigWrapper('./assets/templates');
    $template = $twig->loadTemplate('./backup/main.twig');

    $templateVars = [
        'adminHeaderBackup' => Translation::get('ad_csv_backup'),
        'adminBackupCardHeader' => Translation::get('ad_csv_head'),
        'adminBackupCardBody' => Translation::get('ad_csv_make'),
        'adminBackupLinkData' => Translation::get('ad_csv_linkdat'),
        'adminBackupLinkLogs' => Translation::get('ad_csv_linklog'),
        'csrfToken' => Token::getInstance()->getTokenString('restore'),
        'adminRestoreCardHeader' => Translation::get('ad_csv_head2'),
        'adminRestoreCardBody' => Translation::get('ad_csv_restore'),
        'adminRestoreLabel' => Translation::get('ad_csv_file'),
        'adminRestoreButton' => Translation::get('ad_csv_ok'),
    ];

    echo $template->render($templateVars);
} else {
    require 'no-permission.php';
}
