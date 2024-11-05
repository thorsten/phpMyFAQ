<?php

/**
 * A dummy page used within an IFRAME for warning the user about his next
 * session expiration and to give him the contextual possibility for
 * refreshing the session by clicking <OK>.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Uwe Pries <uwe.pries@digartis.de>
 * @copyright 2006-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-05-08
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Filter;
use phpMyFAQ\Language;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

define('PMF_ROOT_DIR', dirname(__DIR__));

//
// Define the named constant used as a check by any included PHP file
//
const IS_VALID_PHPMYFAQ = null;

//
// Bootstrapping
//
require PMF_ROOT_DIR . '/src/Bootstrap.php';
require PMF_ROOT_DIR . '/translations/language_en.php';

//
// Service Containers
//
$container = new ContainerBuilder();
$loader = new PhpFileLoader($container, new FileLocator(__DIR__));
try {
    $loader->load('../src/services.php');
} catch (Exception $e) {
    echo $e->getMessage();
}

$faqConfig = Configuration::getConfigurationInstance();

//
// Get language (default: english)
//
$language = Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_SPECIAL_CHARS);
if (!is_null($language) && Language::isASupportedLanguage($language)) {
    require PMF_ROOT_DIR . '/translations/language_' . $language . '.php';
}

//
// Set translation class
//
try {
    Translation::create()
        ->setLanguagesDir(PMF_TRANSLATION_DIR)
        ->setDefaultLanguage('en')
        ->setCurrentLanguage($language);
} catch (Exception $e) {
    echo '<strong>Error:</strong> ' . $e->getMessage();
}

//
// Initializing static string wrapper
//
Strings::init($language);

$user = CurrentUser::getCurrentUser($faqConfig);

$refreshTime = (PMF_AUTH_TIMEOUT - PMF_AUTH_TIMEOUT_WARNING) * 60;

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$template = $twig->loadTemplate('./admin/session-keepalive.twig');

$templateVars = [
    'metaLanguage' => Translation::get('metaLanguage'),
    'phpMyFAQVersion' => System::getVersion(),
    'currentYear' => date('Y'),
    'isUserLoggedIn' => $user->isLoggedIn() && ($refreshTime > 0),
    'csrfToken' => Token::getInstance($container->get('session'))->getTokenString('admin-logout'),
    'msgConfirm' => sprintf(Translation::get('ad_session_expiring'), PMF_AUTH_TIMEOUT_WARNING),
    'sessionTimeout' => PMF_AUTH_TIMEOUT,
    'refreshTime' => $refreshTime,
];

echo $template->render($templateVars);
