<?php

/**
 * The 2fa-form for entering the token for two-factor-authentication.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-03-11
 */

use phpMyFAQ\Translation;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Configuration;
use Symfony\Component\HttpFoundation\Request;

$request = Request::createFromGlobals();

$faqConfig = Configuration::getConfigurationInstance();

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$template = $twig->loadTemplate('./admin/user/twofactor.twig');

$templateVars = [
    'msgTwofactorEnabled' => Translation::get('msgTwofactorEnabled'),
    'msgTwofactorCheck' => Translation::get('msgTwofactorCheck'),
    'msgEnterTwofactorToken' => Translation::get('msgEnterTwofactorToken'),
    'requestIsSecure' => $request->isSecure(),
    'security.useSslForLogins' => $faqConfig->get('security.useSslForLogins'),
    'actionIsLogout' => $request->query->get('action') === 'logout',
    'ad_logout' => Translation::get('ad_logout'),
    'error' => $error,
    'requestHost' => $request->getHost(),
    'requestUri' => $request->getRequestUri(),
    'msgSecureSwitch' => Translation::get('msgSecureSwitch'),
    'systemUri' => $faqConfig->getDefaultUrl()
];

echo $template->render($templateVars);
