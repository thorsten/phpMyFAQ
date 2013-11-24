<?php
/**
 * The login form
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Alexander M. Turek <me@derrabus.de>
 * @copyright 2005-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2013-02-05
 */

$templateVars = array(
    'PMF_LANG'             => $PMF_LANG,
    'displayError'         => false,
    'displayLogoutMessage' => $action == 'logout',
    'forceSecureSwitch'    => false
);

if (isset($error) && 0 < strlen($error)) {
    $templateVars['displayError'] = true;
    $templateVars['errorMessage'] = $error;
}

if (!isset($_SERVER['HTTPS']) && $faqConfig->get('security.useSslForLogins')) {
    $templateVars['forceSecureSwitch'] = true;
    $templateVars['secureSwitchUrl']   = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

$twig->loadTemplate('loginform.twig')
    ->display($templateVars);
