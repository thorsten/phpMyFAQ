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
 * @copyright 2022-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-09-09
 */

namespace phpMyFAQ\Auth;

use phpMyFAQ\Auth;
use phpMyFAQ\Auth\Azure\OAuth;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Session;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class AuthAzureActiveDirectory
 *
 * @package phpMyFAQ\Auth
 */
class AuthAzureActiveDirectory extends Auth implements AuthDriverInterface
{
    private readonly Session $session;

    private string $oAuthVerifier = '';

    private string $oAuthChallenge;

    /** @var string */
    private const AAD_CHALLENGE_METHOD = 'S256';

    /** @var string URL to logout */
    private const AAD_LOGOUT_URL = 'https://login.microsoftonline.com/common/wsfederation?wa=wsignout1.0';

    /**
     * @inheritDoc
     */
    public function __construct(Configuration $config, private readonly OAuth $oAuth)
    {
        $this->config = $config;
        $this->session = new Session($config);

        parent::__construct($config);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function create(string $login, string $password, string $domain = ''): mixed
    {
        $user = new User($this->config);
        $result = $user->createUser($login, '', $domain);
        $user->setStatus('active');

        // Set user information from JWT
        $user->setUserData(
            [
                'display_name' => $this->oAuth->getName(),
                'email' => $this->oAuth->getMail(),
            ]
        );

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function update(string $login, string $password): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $login): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function checkCredentials(string $login, string $password, array $optionalData = []): bool
    {
        $this->create($login, '');
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isValidLogin(string $login, array $optionalData = []): int
    {
        if ($login === $this->oAuth->getMail()) {
            return 1;
        }

        return 0;
    }

    /**
     * Method to authorize against Azure AD
     *
     * @throws \Exception
     */
    public function authorize(): void
    {
        $this->createOAuthChallenge();
        $this->session->setCurrentSessionKey();
        $this->session->set(Session::PMF_AZURE_AD_OAUTH_VERIFIER, $this->oAuthVerifier);

        $oAuthURL = sprintf(
            'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize' .
            '?response_type=code&client_id=%s&redirect_uri=%s&scope=%s&code_challenge=%s&code_challenge_method=%s',
            AAD_OAUTH_TENANTID,
            AAD_OAUTH_CLIENTID,
            urlencode($this->config->getDefaultUrl() . 'services/azure/callback.php'),
            AAD_OAUTH_SCOPE,
            $this->oAuthChallenge,
            self::AAD_CHALLENGE_METHOD
        );

        $response = new RedirectResponse($oAuthURL);
        $response->send();
    }

    /**
     * Logout
     *
     */
    public function logout(): void
    {
        // Try to authenticate with cookie information
        $user = CurrentUser::getFromCookie($this->config);

        // authenticate with session information
        if (!$user instanceof CurrentUser) {
            $user = CurrentUser::getFromSession($this->config);
        }

        $user->getUserByLogin($user->getLogin());
        $user->deleteFromSession(true);

        $redirect = new RedirectResponse(self::AAD_LOGOUT_URL);
        $redirect->send();
    }

    /**
     * Method to generate code verifier and code challenge for oAuth login.
     * See RFC7636 for details.
     *
     * @throws \Exception
     */
    private function createOAuthChallenge(): void
    {
        $verifier = $this->oAuthVerifier;

        if (!$this->oAuthVerifier) {
            $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-._~';
            $charLen = strlen($chars) - 1;
            $verifier = '';

            for ($i = 0; $i < 128; $i++) {
                $verifier .= $chars[random_int(0, $charLen)];
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
