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
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-01
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Setup\Upgrade;
use phpMyFAQ\System;
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

$twig = new TwigWrapper('./assets/templates');
$template = $twig->loadTemplate('./configuration/upgrade.twig');

if ($user->perm->hasPermission($user->getUserId(), 'editconfig')) {
    $templateVars = [
        'adminHeaderUpgrade' => Translation::get('ad_menu_upgrade'),
        'releaseEnvironment' => $faqConfig->get('main.releaseEnvironment')
    ];

    $upgrade = new Upgrade(new System(), $faqConfig);

    echo $template->render($templateVars);
} else {
    require 'no-permission.php';
}
