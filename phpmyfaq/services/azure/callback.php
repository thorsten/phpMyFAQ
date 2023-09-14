<?php

/**
 * Callback handler for Microsoft Azure Active Directory
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-09-09
 */

use GuzzleHttp\Exception\GuzzleException;
use phpMyFAQ\Auth\AuthAzureActiveDirectory;
use phpMyFAQ\Configuration;
use phpMyFAQ\Filter;
use phpMyFAQ\Session;
use phpMyFAQ\Auth\Azure\OAuth;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\RedirectResponse;

session_start();
session_regenerate_id(true);

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

$session = new Session($faqConfig);
$oAuth = new OAuth($faqConfig, $session);
$auth = new AuthAzureActiveDirectory($faqConfig, $oAuth);

$redirect = new RedirectResponse($faqConfig->getDefaultUrl());

if ($session->getCurrentSessionKey()) {
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
        $user->setAuthSource('azure');
        $user->updateSessionId(true);
        $user->saveToSession();
        $user->setTokenData([
                'refresh_token' => $oAuth->getRefreshToken(),
                'access_token' => $oAuth->getAccessToken(),
                'code_verifier' => $session->get(Session::PMF_AZURE_AD_OAUTH_VERIFIER),
                'jwt' => $oAuth->getToken()
            ]);
        $user->setSuccess(true);

        // @todo -> redirect to where the user came from
        $redirect->send();

    } catch (GuzzleException $e) {
        echo $e->getMessage();
    } catch (Exception $e) {
        echo $e->getMessage();
    }
} else {
    $redirect->send();
}


