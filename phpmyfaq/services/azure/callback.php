<?php

/**
 * Callback handler for Microsoft Entra ID
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-09-09
 */

use phpMyFAQ\Auth\AuthEntraId;
use phpMyFAQ\Auth\EntraId\OAuth;
use phpMyFAQ\Auth\EntraId\Session as EntraIdSession;
use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\Filter;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(-1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
    session_regenerate_id(true);
}

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

$code = Filter::filterInput(INPUT_GET, 'code', FILTER_SANITIZE_SPECIAL_CHARS);
$error = Filter::filterInput(INPUT_GET, 'error_description', FILTER_SANITIZE_SPECIAL_CHARS);

$session = new Session(new PhpBridgeSessionStorage());
$session->start();

$entraIdSession = new EntraIdSession($faqConfig, $session);
$oAuth = new OAuth($faqConfig, $entraIdSession);
$auth = new AuthEntraId($faqConfig, $oAuth);

$redirect = new RedirectResponse($faqConfig->getDefaultUrl());

if ($entraIdSession->getCurrentSessionKey()) {
    try {
        $token = $oAuth->getOAuthToken($code);
        $oAuth->setToken($token)->setAccessToken($token->access_token)->setRefreshToken($token->refresh_token);

        $user = new CurrentUser($faqConfig);

        if (!$auth->isValidLogin($oAuth->getMail())) {
            // @todo proper error handling
            echo 'Login not valid.';
            exit();
        }

        if (!$auth->checkCredentials($oAuth->getMail(), '')) {
            // @todo proper error handling
            echo 'Credentials not valid.';
            exit();
        }

        $user->getUserByLogin($oAuth->getMail());
        $user->setLoggedIn(true);
        $user->setAuthSource(AuthenticationSourceType::AUTH_AZURE->value);
        $user->updateSessionId(true);
        $user->saveToSession();
        $user->setTokenData([
                'refresh_token' => $oAuth->getRefreshToken(),
                'access_token' => $oAuth->getAccessToken(),
                'code_verifier' => $entraIdSession->get(EntraIdSession::ENTRA_ID_OAUTH_VERIFIER),
                'jwt' => $oAuth->getToken()
            ]);
        $user->setSuccess(true);

        // @todo -> redirect to where the user came from
        $redirect->send();
    } catch (TransportExceptionInterface $exception) {
        echo sprintf(
            'Entra ID Login failed: %s at line %d at %s',
            $exception->getMessage(),
            $exception->getLine(),
            $exception->getFile()
        );
    } catch (Exception $exception) {
        echo sprintf(
            'Entra ID Login failed: %s at line %d at %s',
            $exception->getMessage(),
            $exception->getLine(),
            $exception->getFile()
        );
    }
} else {
    $redirect->send();
}


