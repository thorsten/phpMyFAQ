<?php

/**
 * Manages user authentication with Microsoft Azure Active Directory.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-09-09
 */

namespace phpMyFAQ\Auth;

use phpMyFAQ\Auth;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;

/**
 * Class AuthAzureActiveDirectory
 *
 * @package phpMyFAQ\Auth
 */
class AuthAzureActiveDirectory extends Auth implements AuthDriverInterface
{
    /** @var Azure\OAuth */
    private Auth\Azure\OAuth $oAuth;

    private string $oAuthVerifier = '';

    private string $oAuthChallenge;

    private const CHALLENGE_METHOD = 'S256';

    /**
     * @inheritDoc
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->oAuth = new Auth\Azure\OAuth();

        parent::__construct($config);

        $this->login();
    }

    public function create(string $login, string $password, string $domain = ''): mixed
    {
        // TODO: Implement create() method.
    }

    public function update(string $login, string $password): bool
    {
        // TODO: Implement update() method.
    }

    public function delete(string $login): bool
    {
        // TODO: Implement delete() method.
    }

    public function checkCredentials(string $login, string $password, array $optionalData = []): bool
    {
        // TODO: Implement checkCredentials() method.
    }

    public function isValidLogin(string $login, array $optionalData = []): int
    {
        // TODO: Implement isValidLogin() method.
    }

    /**
     * Method to login
     *
     */
    private function login()
    {
        $this->oAuthChallenge();
        $this->redirect();
    }

    /**
     * Redirect to oAuth URL
     */
    private function redirect()
    {
        $oAuthURL = sprintf(
            'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize' .
            '?response_type=code&client_id=%s&redirect_uri=%s&scope=%s&code_challenge=%s&code_challenge_method=%s',
            AAD_OAUTH_TENANTID,
            AAD_OAUTH_CLIENTID,
            urlencode($this->config->getDefaultUrl() . 'services/azure/callback.php'),
            AAD_OAUTH_SCOPE,
            $this->oAuthChallenge,
            self::CHALLENGE_METHOD
        );
        header('Location: ' . $oAuthURL);
    }

    /**
     * Method to generate code verifier and code challenge for oAuth login.
     * See RFC7636 for details.
     */
    private function oAuthChallenge()
    {
        $verifier = $this->oAuthVerifier;

        if (!$this->oAuthVerifier) {
            $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-._~';
            $charLen = strlen($chars) - 1;
            $verifier = '';

            for ($i = 0; $i < 128; $i++) {
                $verifier .= $chars[mt_rand(0, $charLen)];
            }

            $this->oAuthVerifier = $verifier;
        }

        $this->oAuthChallenge = str_replace(
            '=',
            '',
            strtr(base64_encode(pack('H*', hash('sha256', $verifier))), '+/', '-_')
        );
    }
}
