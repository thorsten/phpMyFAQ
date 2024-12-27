<?php

/**
 * The main admin backend index file.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Bastian Poettner <bastian@poettner.net>
 * @author    Meikel Katzengreis <meikel@katzengreis.com>
 * @author    Minoru TODA <todam@netjapan.co.jp>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2002-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-09-16
 */

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Language;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\TwoFactor;
use phpMyFAQ\User\UserAuthentication;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

define('PMF_ROOT_DIR', dirname(__DIR__));

//
// Define the named constant used as a check by any included PHP file
//
const IS_VALID_PHPMYFAQ = null;

//
// Bootstrapping
//
require PMF_ROOT_DIR . '/src/Bootstrap.php';

//
// Create Response & Request
//
$response = new Response();
$request = Request::createFromGlobals();

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

$faqConfig = $container->get('phpmyfaq.configuration');

//
// Get language (default: english)
//
$Language = $container->get('phpmyfaq.language');
$faqLangCode = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));
$faqConfig->setLanguage($Language);

if (!Language::isASupportedLanguage($faqLangCode)) {
    $faqLangCode = 'en';
}

//
// Set translation class
//
try {
    Translation::create()
            ->setLanguagesDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage($faqLangCode)
            ->setMultiByteLanguage();
} catch (Exception $e) {
    echo '<strong>Error:</strong> ' . $e->getMessage();
}

//
// Initializing static string wrapper
//
Strings::init($faqLangCode);

//
// Set actual template set name
//
TwigWrapper::setTemplateSetName($faqConfig->get('layout.templateSet'));

//
// Initialize attachment factory
//
AttachmentFactory::init(
    $faqConfig->get('records.defaultAttachmentEncKey'),
    $faqConfig->get('records.enableAttachmentEncryption')
);

//
// Create a new phpMyFAQ system object
//
$faqSystem = new System();

//
// Create a new FAQ object
//
$faq = new Faq($faqConfig);

//
// Get user action
//
$action = Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
if (is_null($action)) {
    $action = Filter::filterInput(INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
}
if (!is_null($action)) {
    $action = Strings::htmlentities($action);
}

//
// Get possible redirect action
//
$redirectAction = Filter::filterInput(INPUT_POST, 'redirect-action', FILTER_SANITIZE_SPECIAL_CHARS);
if (is_null($action) && '' !== $redirectAction && 'logout' !== $redirectAction) {
    $action = $redirectAction;
}

//
// Authenticate current user
//
$error = '';
$faqusername = Filter::filterVar($request->request->get('faqusername'), FILTER_SANITIZE_SPECIAL_CHARS);
$faqpassword = Filter::filterVar($request->request->get('faqpassword'), FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);
$rememberMe = Filter::filterVar($request->request->get('faqrememberme'), FILTER_VALIDATE_BOOLEAN);
$token = Filter::filterVar($request->request->get('token'), FILTER_SANITIZE_SPECIAL_CHARS);
$userid = Filter::filterVar($request->request->get('userid'), FILTER_VALIDATE_INT);

//
// Logging user in if 2FA is enabled and token is given and validated, if not: returns error message
//
if (!is_null($token) && !is_null($userid)) {
    $user = new CurrentUser($faqConfig);
    $user->getUserById($userid);
    if (strlen((string) $token) === 6 && is_numeric((string) $token)) {
        $tfa = new TwoFactor($faqConfig, $user);
        $res = $tfa->validateToken($token, $userid);
        if (!$res) {
            $error = Translation::get('msgTwofactorErrorToken');
            $action = 'twofactor';
        } else {
            $user->twoFactorSuccess();
            require 'header.php';
            require 'dashboard.php';
            exit();
        }
    } else {
        $error = Translation::get('msgTwofactorErrorToken');
        $action = 'twofactor';
    }
}

if (!isset($user)) {
    $user = new CurrentUser($faqConfig);
}

//
// Set username via SSO
//
if ($faqConfig->get('security.ssoSupport') && $request->server->get('REMOTE_USER') !== null) {
    $faqusername = trim((string) $request->server->get('REMOTE_USER'));
    $faqpassword = '';
}

//
// Login via local DB or LDAP or SSO
//
if ($faqusername !== '' && ($faqpassword !== '' || $faqConfig->get('security.ssoSupport'))) {
    $userAuth = new UserAuthentication($faqConfig, $user);
    $userAuth->setRememberMe($faqremember ?? false);
    try {
        $user = $userAuth->authenticate($faqusername, $faqpassword);
        $userid = $user->getUserId();
        if ($userAuth->hasTwoFactorAuthentication()) {
            $action = 'twofactor';
        }
    } catch (Exception $e) {
        $logging = new AdminLog($faqConfig);
        $logging->log($user, 'Login-error\nLogin: ' . $faqusername . '\nErrors: ' . implode(', ', $user->errors));
        $action = 'login';
        $error = $e->getMessage();
    }
} else {
    // Try to authenticate with cookie information
    $user = CurrentUser::getCurrentUser($faqConfig);
}

if (isset($userAuth)) {
    if ($userAuth instanceof UserAuthentication) {
        if ($userAuth->hasTwoFactorAuthentication()) {
            $action = 'twofactor';
        }
    }
}

//
// Logout
//
$csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);
if (
    $csrfToken &&
    Token::getInstance($container->get('session'))->verifyToken('admin-logout', $csrfToken) &&
    $action === 'logout' &&
    $user->isLoggedIn()
) {
    $user->deleteFromSession(true);
    $ssoLogout = $faqConfig->get('security.ssoLogoutRedirect');
    if ($faqConfig->get('security.ssoSupport') && !empty($ssoLogout)) {
        $response->isRedirect($ssoLogout);
        $response->send();
        exit();
    }
}

//
// Get current admin user and group id - default: -1
//
[$currentAdminUser, $currentAdminGroups] = CurrentUser::getCurrentUserGroupId($user);

// Header of the admin page including the navigation
require 'header.php';

if ($action === 'twofactor') {
    require 'twofactor.php';
    exit();
}

$numRights = $user->perm->getUserRightsCount($user);

// User is authenticated
if ($user->isLoggedIn() && $user->getUserId() > 0 && ($numRights > 0 || $user->isSuperAdmin())) {
    if (!is_null($action)) {
        // the various sections of the admin area
        switch ($action) {
            // Config administration
            case 'forms-translations':
                require 'forms.translations.php';
                break;

            default:
                echo 'Dave, this conversation can serve no purpose anymore. Goodbye.';
                break;
        }
    } else {
        require 'dashboard.php';
    }
// User is authenticated but has no rights
} elseif ($user->isLoggedIn() && $numRights === 0) {
    require __DIR__ . '/no-permission.php';
// User is NOT authenticated
} else {
    //$error = Translation::get('msgSessionExpired');
    require 'login.php';
}

require 'footer.php';
