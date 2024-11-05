<?php

/**
 * Login handler for Microsoft Entra ID
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-09-09
 */

use phpMyFAQ\Auth\AuthEntraId;
use phpMyFAQ\Auth\EntraId\OAuth;
use phpMyFAQ\Auth\EntraId\Session as EntraIdSession;
use phpMyFAQ\Configuration;

//
// Prepend and start the PHP session
//
define('PMF_ROOT_DIR', dirname(__DIR__, 2));
const IS_VALID_PHPMYFAQ = null;

//
// Bootstrapping
//
require PMF_ROOT_DIR . '/src/Bootstrap.php';
require PMF_CONFIG_DIR . '/azure.php';

$faqConfig = Configuration::getConfigurationInstance();

$enraIdSession = new EntraIdSession($faqConfig, $session);
$oAuth = new OAuth($faqConfig, $enraIdSession);
$auth = new AuthEntraId($faqConfig, $oAuth);

$auth->logout();
