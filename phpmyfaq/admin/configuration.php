<?php

/**
 * The main configuration frontend.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-12-26
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

if ($user->perm->hasPermission($user->getUserId(), 'editconfig')) {
    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $template = $twig->loadTemplate('./admin/configuration/main.twig');

    $templateVars = [
        'adminHeaderConfiguration' => Translation::get('ad_config_edit'),
        'csrfToken' => Token::getInstance()->getTokenString('configuration'),
        'adminConfigurationButtonReset' => Translation::get('ad_config_reset'),
        'adminConfigurationButtonSave' => Translation::get('ad_config_save'),
        'adminConfigurationMainTab' => Translation::get('mainControlCenter'),
        'adminConfigurationFaqsTab' => Translation::get('recordsControlCenter'),
        'adminConfigurationSearchTab' => Translation::get('searchControlCenter'),
        'adminConfigurationSecurityTab' => Translation::get('securityControlCenter'),
        'adminConfigurationSpamTab' => Translation::get('spamControlCenter'),
        'adminConfigurationSeoTab' => Translation::get('seoCenter'),
        'adminConfigurationMailTab' => Translation::get('mailControlCenter'),
        'adminConfigurationUpgradeTab' => Translation::get('upgradeControlCenter'),
    ];

    echo $template->render($templateVars);
} else {
    require 'no-permission.php';
}
