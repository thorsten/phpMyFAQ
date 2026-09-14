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
 * @copyright 2022-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-09-09
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth;

use Closure;
use phpMyFAQ\Auth;
use phpMyFAQ\Auth\EntraId\EntraIdSession;
use phpMyFAQ\Auth\EntraId\OAuth;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\User;
use SensitiveParameter;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class AuthEntraId
 *
 * Local accounts are bound to the immutable Entra ID object identifier. An existing
 * account is only ever used by the identity whose object identifier it stores; it is
 * never claimed by a matching login or mail address.
 *
 * @package phpMyFAQ\Auth
 */
class AuthEntraId extends Auth implements AuthDriverInterface
{
    private string $oAuthVerifier = '';

    private string $oAuthChallenge;

    private string $oAuthState = '';

    private string $oAuthNonce = '';

    private int $authenticatedUserId = 0;

    private const string ENTRAID_CHALLENGE_METHOD = 'S256';

    private const string ENTRAID_LOGOUT_URL = 'https://login.microsoftonline.com/common/wsfederation?wa=wsignout1.0';

    private const int OAUTH_STATE_LIFETIME = 7200;

    /**
     * @param (Closure(): User)|null $userFactory
     */
    public function __construct(
        Configuration $configuration,
        private readonly OAuth $oAuth,
        private readonly ?Closure $userFactory = null,
    ) {
        $this->configuration = $configuration;

        parent::__construct($configuration);
    }

    /**
     * Creates the local account for the signed-in identity and links it to the
     * object identifier. Returns false when the login is already taken, so a
     * pre-existing account is never modified.
     *
     * @inheritDoc
     * @throws Exception
     */
    public function create(string $login, #[SensitiveParameter] string $password, string $domain = ''): bool
    {
        $user = $this->createUser();

        try {
            $result = $user->createUser($login, '', $domain);
        } catch (\Exception $exception) {
            $this->configuration
                ->getLogger()
                ->error(sprintf(
                    'Entra ID user creation failed for "%s": %s',
                    $this->redactIdentifier($login),
                    $exception->getMessage(),
                ));
            return false;
        }

        if (!$result) {
            return false;
        }

        $saved = $user->setUserData([
            'display_name' => $this->oAuth->getName(),
            'email' => $this->oAuth->getMail(),
            'entra_oid' => $this->oAuth->getObjectId(),
        ]);

        if (!$saved) {
            $this->configuration
                ->getLogger()
                ->error(sprintf('Entra ID user data persistence failed for "%s"', $this->redactIdentifier($login)));
            return false;
        }

        $user->setStatus('active');
        $user->setAuthSource(AuthenticationSourceType::AUTH_AZURE->value);

        $this->authenticatedUserId = $user->getUserId();

        return true;
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
     * Accepts the login when an account is linked to the object identifier of the
     * signed-in identity, or creates one when the login is still free. A login that
     * exists but is not linked is refused.
     *
     * @inheritDoc
     * @throws Exception
     */
    public function checkCredentials(
        string $login,
        #[SensitiveParameter]
        string $password,
        ?array $optionalData = [],
    ): bool {
        $this->authenticatedUserId = 0;

        if ($login === '' || $login !== $this->oAuth->getMail()) {
            return false;
        }

        $objectId = $this->oAuth->getObjectId();
        if ($objectId === '') {
            $this->configuration
                ->getLogger()
                ->warning(sprintf(
                    'Entra ID login rejected for "%s": the token carries no object identifier.',
                    $this->redactIdentifier($login),
                ));
            return false;
        }

        $linkedUser = $this->findLinkedUser($objectId);
        if ($linkedUser instanceof User) {
            if ($linkedUser->getStatus() === 'blocked') {
                $this->configuration
                    ->getLogger()
                    ->warning(sprintf(
                        'Entra ID login rejected for "%s": the linked local account is blocked.',
                        $this->redactIdentifier($login),
                    ));
                return false;
            }

            $this->authenticatedUserId = $linkedUser->getUserId();

            return true;
        }

        if ($this->findUser($login) instanceof User) {
            $this->configuration
                ->getLogger()
                ->warning(sprintf(
                    'Entra ID login rejected for "%s": the local account is not linked to this Entra ID identity.',
                    $this->redactIdentifier($login),
                ));
            return false;
        }

        return $this->create($login, '');
    }

    /**
     * @inheritDoc
     */
    public function isValidLogin(string $login, ?array $optionalData = []): int
    {
        if ($login !== '' && $login === $this->oAuth->getMail()) {
            return 1;
        }

        return 0;
    }

    /**
     * Returns the ID of the local account resolved by the last successful
     * checkCredentials() call, or 0 if none was resolved.
     */
    public function getAuthenticatedUserId(): int
    {
        return $this->authenticatedUserId;
    }

    /**
     * Method to authorize against Entra ID
     *
     * @throws \Exception
     */
    public function authorize(): RedirectResponse
    {
        $this->createOAuthChallenge();
        $this->createOAuthState();
        $this->createOAuthNonce();
        $entraIdSession = $this->oAuth->getEntraIdSession();
        $entraIdSession->setCurrentSessionKey();
        $entraIdSession->set(EntraIdSession::ENTRA_ID_OAUTH_VERIFIER, $this->oAuthVerifier);
        $entraIdSession->set(EntraIdSession::ENTRA_ID_OAUTH_STATE, $this->oAuthState);
        $entraIdSession->set(EntraIdSession::ENTRA_ID_OAUTH_NONCE, $this->oAuthNonce);
        // The session cookie is SameSite=Strict and therefore absent on the cross-site
        // redirect back from Microsoft, so both values are mirrored into lax cookies.
        $entraIdSession->setCookie(
            EntraIdSession::ENTRA_ID_OAUTH_VERIFIER,
            $this->oAuthVerifier,
            self::OAUTH_STATE_LIFETIME,
            false,
        );
        $entraIdSession->setCookie(
            EntraIdSession::ENTRA_ID_OAUTH_STATE,
            $this->oAuthState,
            self::OAUTH_STATE_LIFETIME,
            false,
        );
        $entraIdSession->setCookie(
            EntraIdSession::ENTRA_ID_OAUTH_NONCE,
            $this->oAuthNonce,
            self::OAUTH_STATE_LIFETIME,
            false,
        );

        $oAuthURL = sprintf(
            'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize'
            . '?response_type=code&client_id=%s&redirect_uri=%s&scope=%s&code_challenge=%s&code_challenge_method=%s'
            . '&state=%s&nonce=%s',
            AAD_OAUTH_TENANTID,
            AAD_OAUTH_CLIENTID,
            urlencode($this->configuration->getDefaultUrl() . 'services/azure/callback.php'),
            AAD_OAUTH_SCOPE,
            $this->oAuthChallenge,
            self::ENTRAID_CHALLENGE_METHOD,
            $this->oAuthState,
            $this->oAuthNonce,
        );

        return new RedirectResponse($oAuthURL);
    }

    /**
     * Returns true when the state returned by Entra ID matches the one issued by
     * authorize() for this browser, which ties the callback to the login it started.
     */
    public function isValidState(string $state): bool
    {
        if ($state === '') {
            return false;
        }

        $entraIdSession = $this->oAuth->getEntraIdSession();
        $expected = (string) ($entraIdSession->get(EntraIdSession::ENTRA_ID_OAUTH_STATE) ?? '');
        if ($expected === '') {
            $expected = $entraIdSession->getCookie(EntraIdSession::ENTRA_ID_OAUTH_STATE);
        }

        return $expected !== '' && hash_equals($expected, $state);
    }

    /**
     * Logout
     *
     */
    public function logout(): RedirectResponse
    {
        return new RedirectResponse(self::ENTRAID_LOGOUT_URL);
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
            search: '=',
            replace: '',
            subject: strtr(string: base64_encode(pack('H*', hash('sha256', $verifier))), from: '+/', to: '-_'),
        );
    }

    /**
     * Generates the unguessable OAuth state parameter.
     *
     * @throws \Exception
     */
    private function createOAuthState(): void
    {
        if ($this->oAuthState !== '') {
            return;
        }

        $this->oAuthState = bin2hex(random_bytes(32));
    }

    /**
     * Generates the nonce that Entra ID echoes back in the ID token, which ties the
     * token to the authorization request this browser started.
     *
     * @throws \Exception
     */
    private function createOAuthNonce(): void
    {
        if ($this->oAuthNonce !== '') {
            return;
        }

        $this->oAuthNonce = bin2hex(random_bytes(32));
    }

    /**
     * Returns the account linked to the given object identifier, blocked or not.
     *
     * @throws Exception
     */
    private function findLinkedUser(string $objectId): ?User
    {
        $user = $this->createUser();
        $userId = $user->getUserIdByEntraOid($objectId);
        if ($userId <= 0) {
            return null;
        }

        return $user->getUserById($userId, allowBlockedUsers: true) ? $user : null;
    }

    /**
     * @throws Exception
     */
    private function findUser(string $login): ?User
    {
        $user = $this->createUser();

        return $user->getUserByLogin($login, false) ? $user : null;
    }

    private function redactIdentifier(string $identifier): string
    {
        if (str_contains($identifier, '@')) {
            [$local, $domain] = explode('@', string: $identifier, limit: 2);
            return ($local === '' ? '' : $local[0]) . '***@' . $domain;
        }

        if (mb_strlen($identifier) <= 3) {
            return str_repeat('*', mb_strlen($identifier));
        }

        return mb_substr($identifier, start: 0, length: 3) . '…';
    }

    /**
     * @throws Exception
     */
    private function createUser(): User
    {
        if ($this->userFactory instanceof Closure) {
            return ($this->userFactory)();
        }

        return new User($this->configuration);
    }
}
