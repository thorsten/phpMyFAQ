<?php

/**
 * Manages user authentication with Microsoft Entra ID.
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

namespace phpMyFAQ\Auth;

use phpMyFAQ\Auth;
use phpMyFAQ\Auth\EntraId\OAuth;
use phpMyFAQ\Auth\EntraId\Session;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\User;
use SensitiveParameter;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**q
 * Class AuthEntraId
 *
 * @package phpMyFAQ\Auth
 */
class AuthEntraId extends Auth implements AuthDriverInterface
{
    private string $oAuthVerifier = '';

    private string $oAuthChallenge;

    /** @var string */
    private const ENTRAID_CHALLENGE_METHOD = 'S256';

    /** @var string URL to logout */
    private const ENTRAID_LOGOUT_URL = 'https://login.microsoftonline.com/common/wsfederation?wa=wsignout1.0';

    /**
     * @inheritDoc
     */
    public function __construct(
        Configuration $configuration,
        private readonly OAuth $oAuth
    ) {
        $this->configuration = $configuration;

        parent::__construct($configuration);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function create(string $login, #[SensitiveParameter] string $password, string $domain = ''): mixed
    {
        $user = new User($this->configuration);
        $result = $user->createUser($login, '', $domain);
        $user->setStatus('active');
        $user->setAuthSource(AuthenticationSourceType::AUTH_AZURE->value);

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
    public function update(string $login, #[SensitiveParameter] string $password): bool
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
    public function checkCredentials(
        string $login,
        #[SensitiveParameter] string $password,
        ?array $optionalData = []
    ): bool {
        $this->create($login, '');
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isValidLogin(string $login, ?array $optionalData = []): int
    {
        if ($login === $this->oAuth->getMail()) {
            return 1;
        }

        return 0;
    }

    /**
     * Method to authorize against Entra ID
     *
     * @throws \Exception
     */
    public function authorize(): void
    {
        $this->createOAuthChallenge();
        $this->oAuth->getSession()->setCurrentSessionKey();
        $this->oAuth->getSession()->set(Session::ENTRA_ID_OAUTH_VERIFIER, $this->oAuthVerifier);
        $this->oAuth->getSession()->setCookie(Session::ENTRA_ID_OAUTH_VERIFIER, $this->oAuthVerifier, 7200, false);

        $oAuthURL = sprintf(
            'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize' .
            '?response_type=code&client_id=%s&redirect_uri=%s&scope=%s&code_challenge=%s&code_challenge_method=%s',
            AAD_OAUTH_TENANTID,
            AAD_OAUTH_CLIENTID,
            urlencode($this->configuration->getDefaultUrl() . 'services/azure/callback.php'),
            AAD_OAUTH_SCOPE,
            $this->oAuthChallenge,
            self::ENTRAID_CHALLENGE_METHOD
        );

        $redirectResponse = new RedirectResponse($oAuthURL);
        $redirectResponse->send();
    }

    /**
     * Logout
     *
     */
    public function logout(): void
    {
        $redirectResponse = new RedirectResponse(self::ENTRAID_LOGOUT_URL);
        $redirectResponse->send();
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

        if ($this->oAuthVerifier === '' || $this->oAuthVerifier === '0') {
            $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-._~';
            $charLen = strlen($chars) - 1;
            $verifier = '';

            for ($i = 0; $i < 128; ++$i) {
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
