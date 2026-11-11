<?php

/**
 * The WebAuthn Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-09-11
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Auth\AuthWebAuthn;
use phpMyFAQ\Auth\WebAuthn\WebAuthnUser;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Controller\Administration\SkipsAuthenticationCheck;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\RegistrationHelper;
use phpMyFAQ\Mail;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Utils;
use Random\RandomException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

final class WebAuthnController extends AbstractController implements SkipsAuthenticationCheck
{
    /** Session key holding the time of the last successful passkey assertion. */
    public const string SESSION_PASSKEY_ASSERTED_AT = 'webauthn_asserted_at';

    /** Seconds a passkey assertion counts as fresh for adding another passkey. */
    public const int PASSKEY_REAUTH_WINDOW = 300;

    /**
     * Account id of session state staged for a passwordless sign-up. No account exists at that
     * point; register() creates it once the ceremony is complete.
     */
    public const string PENDING_SIGN_UP_ID = '0';

    private readonly AuthWebAuthn $authWebAuthn;

    private readonly User $user;

    /**
     * Optional CurrentUser used by login(); when null a fresh instance is created per request.
     * Injectable so the two-factor deferral in login() can be verified without a database.
     */
    private readonly ?CurrentUser $loginCurrentUser;

    public function __construct(
        ?AuthWebAuthn $authWebAuthn = null,
        ?User $user = null,
        ?CurrentUser $loginCurrentUser = null,
    ) {
        parent::__construct();

        $this->authWebAuthn = $authWebAuthn ?? new AuthWebAuthn($this->configuration);
        $this->user = $user ?? new User($this->configuration);
        $this->loginCurrentUser = $loginCurrentUser;
    }

    /**
     * @throws RandomException|\JsonException
     * @throws \Exception
     */
    #[Route(path: 'api/webauthn/prepare', name: 'api.private.webauthn.prepare', methods: ['POST'])]
    public function prepare(Request $request): JsonResponse
    {
        if (!$this->configuration->get(item: 'security.enableWebAuthnSupport')) {
            return $this->json(['error' => 'WebAuthn support is disabled.'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->configuration->get(item: 'security.enableRegistration')) {
            return $this->json(['error' => 'User registration is disabled.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);

        $csrfToken = Filter::filterVar($data->csrfToken ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        if (!Token::getInstance($this->container->get(id: 'session'))->verifyToken('webauthn-prepare', $csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        $username = Filter::filterVar($data->username, FILTER_SANITIZE_SPECIAL_CHARS);

        $userExists = $this->user->getUserByLogin($username, raiseError: false);

        $isLoggedIn = $this->currentUser instanceof CurrentUser && $this->currentUser->isLoggedIn();

        // Adding a passkey to an existing account is only allowed for the authenticated owner of
        // that account; otherwise an anonymous caller could attach a credential to any account
        // (e.g. admin) and take it over.
        $isOwner = $userExists && $isLoggedIn && $this->currentUser->getUserId() === $this->user->getUserId();

        // From here on the lookup result must not change the answer for anyone but the owner, or
        // the endpoint turns into a user oracle. A logged-in caller is refused for every login
        // that is not their own, existing or not.
        if (!$isOwner && $isLoggedIn) {
            return $this->json(['error' => Translation::get(key: 'err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        if ($isOwner) {
            // The session alone is not enough. A passkey is a durable, password-independent
            // credential: planted through a hijacked session it would outlive logout, remember-me
            // revocation and even a password reset. The same step-up that guards password
            // changes, 2FA removal and e-mail changes applies here (CWE-620).
            $stepUp = $this->requireEnrollmentStepUp(
                (string) ($data->currentPassword ?? ''),
                $this->container->get(id: 'session'),
            );
            if ($stepUp instanceof JsonResponse) {
                return $stepUp;
            }
        }

        if (!$isOwner) {
            // An anonymous request is a passwordless sign-up, and it is treated as one whether or
            // not the login is taken: the same checks, the same challenge, nothing taken from an
            // existing account and no account created here. register() creates the account after
            // a completed ceremony and discards the sign-up if the login already belongs to someone.
            //
            // A sign-up is a registration and must follow the same policy as /api/register: the
            // username doubles as the e-mail address, so the domain whitelist applies to it.
            $registrationHelper = new RegistrationHelper($this->configuration);
            if (!$registrationHelper->isDomainAllowed($username)) {
                return $this->json(['error' => 'The domain is not allowed.'], Response::HTTP_BAD_REQUEST);
            }

            if (!$this->user->isValidLogin($username)) {
                return $this->json(['error' => User::ERROR_USER_LOGINNAME_TOO_SHORT], Response::HTTP_BAD_REQUEST);
            }
        }

        // The user handle of a sign-up is random: there is no account id yet, and the id of an
        // existing account must not reach an anonymous caller.
        $userId = $isOwner ? (string) $this->user->getUserId() : self::PENDING_SIGN_UP_ID;
        $challenge = $this->authWebAuthn->prepareChallengeForRegistration(
            $username,
            $isOwner ? $userId : bin2hex(random_bytes(16)),
        );

        // Seed the ceremony with the keys already on the account. AuthWebAuthn::register() merges
        // the new key into this list; seeded with '' it would return only the new key and
        // register() would overwrite every passkey the owner already has.
        $webAuthnUser = new WebAuthnUser();
        $webAuthnUser
            ->setName($username)
            ->setId($userId)
            ->setWebAuthnKeys($isOwner ? $this->user->getWebAuthnKeys() : '')
            ->setChallenge($challenge['b64challenge'])
            ->setAppId($this->authWebAuthn->getAppId());

        $this->authWebAuthn->storeUserInSession($webAuthnUser);

        return $this->json([
            'challenge' => $challenge,
            'csrfToken' => Token::getInstance($this->container->get(id: 'session'))->getTokenString(
                'webauthn-register',
            ),
        ], Response::HTTP_OK);
    }

    /**
     * Requires proof of the account's existing credential before a passkey is added.
     *
     * Password accounts re-enter the current password; failures consume the shared login
     * failure budget and lock the step-up once it is exhausted (CWE-307). Passwordless accounts
     * have no password to ask for, so a recent passkey assertion (see login()) stands in: the
     * current session must have authenticated with a passkey within the last
     * self::PASSKEY_REAUTH_WINDOW seconds.
     *
     * Returns a JsonResponse that short-circuits the request, or null when the caller may proceed.
     */
    private function requireEnrollmentStepUp(
        #[\SensitiveParameter]
        string $currentPassword,
        SessionInterface $session,
    ): ?JsonResponse {
        if ($this->user->getUserAuthSource() === AuthenticationSourceType::AUTH_WEB_AUTHN->value) {
            $assertedAt = (int) $session->get(self::SESSION_PASSKEY_ASSERTED_AT, 0);
            if ($assertedAt <= 0 || (time() - $assertedAt) > self::PASSKEY_REAUTH_WINDOW) {
                return $this->json(['error' => Translation::get(key: 'err_NotAuth')], Response::HTTP_FORBIDDEN);
            }

            return null;
        }

        if ($this->currentUser->isStepUpLockedOut()) {
            return $this->json([
                'error' => Translation::get(key: 'msgStepUpLockedOut'),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        if (!$this->currentUser->verifyPassword($currentPassword)) {
            $this->currentUser->stepUpFailure();

            return $this->json([
                'error' => Translation::get(key: 'ad_user_error_currentPasswordInvalid'),
            ], Response::HTTP_FORBIDDEN);
        }

        $this->currentUser->stepUpSuccess();

        return null;
    }

    /**
     * Tells the account owner that a passkey was added, so an enrollment made through a
     * hijacked session does not go unnoticed. A failing mail must not undo the enrollment.
     */
    private function notifyOwnerAboutNewPasskey(): void
    {
        try {
            $this->user->mailUser(
                Utils::resolveMarkers(Translation::get(key: 'msgPasskeyAddedMailSubject'), $this->configuration),
                sprintf(
                    Translation::get(key: 'msgPasskeyAddedMailBody'),
                    $this->user->getLogin(),
                    date('Y-m-d H:i:s'),
                    $this->configuration->getDefaultUrl() . 'user/ucp',
                ),
            );
        } catch (\Throwable $throwable) {
            $this->configuration
                ->getLogger()
                ->warning(message: 'Could not notify the user about a new passkey: ' . $throwable->getMessage());
        }
    }

    /**
     * Tells the administrator about a passwordless sign-up, like RegistrationHelper::createUser()
     * does for password registrations. A failing mail must not break the sign-up itself.
     */
    private function notifyAdminAboutNewAccount(string $username, bool $isNowActive): void
    {
        $adminMessage = $isNowActive
            ? 'This user has been automatically activated, you can still'
            . ' modify the users permissions or decline membership by visiting the admin section'
            : 'To activate this user please use';

        try {
            $mail = new Mail($this->configuration);
            $mail->addTo($this->configuration->getAdminEmail());
            $mail->subject = Utils::resolveMarkers(Translation::get(key: 'emailRegSubject'), $this->configuration);
            $mail->message = sprintf(
                'A new user has been registered via passkey:<br><br>Login name: %s<br><br>%s the administration at %s.',
                $username,
                $adminMessage,
                $this->configuration->getDefaultUrl() . '/admin/',
            );
            $mail->send();
        } catch (\Throwable $throwable) {
            $this->configuration
                ->getLogger()
                ->warning(
                    message: 'Could not notify the administrator about a new passkey account: '
                        . $throwable->getMessage(),
                );
        }
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    #[Route(path: 'api/webauthn/register', name: 'api.private.webauthn.register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        if (!$this->configuration->get(item: 'security.enableWebAuthnSupport')) {
            return $this->json(['error' => 'WebAuthn support is disabled.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);

        $csrfToken = Filter::filterVar($data->csrfToken ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        if (!Token::getInstance($this->container->get(id: 'session'))->verifyToken('webauthn-register', $csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        $register = Filter::filterVar($data->register, FILTER_SANITIZE_SPECIAL_CHARS);

        $webAuthnUser = $this->authWebAuthn->getUserFromSession();
        if (!$webAuthnUser instanceof WebAuthnUser) {
            return $this->json(['error' => Translation::get(key: 'err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        // Multisite instances share one PHP session store, so the state prepare() stored may have
        // been issued by another instance where the ownership check passed for a different
        // account with the same login. Only accept state issued by this instance; an empty appId
        // (state from before this check) is rejected as well.
        if (
            $webAuthnUser->getAppId() === ''
            || !hash_equals($this->authWebAuthn->getAppId(), $webAuthnUser->getAppId())
        ) {
            return $this->json(['error' => Translation::get(key: 'err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        if ($webAuthnUser->getId() === self::PENDING_SIGN_UP_ID) {
            return $this->completeSignUp($register, $webAuthnUser);
        }

        try {
            $this->user->getUserByLogin($webAuthnUser->getName());
        } catch (Exception) {
            return $this->json(['error' => Translation::get(key: 'ad_auth_fail')], Response::HTTP_BAD_REQUEST);
        }

        // The account the login resolves to here must be the one prepare() staged. The check is
        // enforced again, independently of prepare(), so a stale or foreign session cannot write
        // keys into a different account.
        if ($this->user->getUserId() !== (int) $webAuthnUser->getId()) {
            return $this->json(['error' => Translation::get(key: 'err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        // Only the authenticated owner adds a passkey to an existing account. A sign-up never
        // gets here: it has no account yet and is handled by completeSignUp() above.
        $isOwner =
            $this->currentUser instanceof CurrentUser
            && $this->currentUser->isLoggedIn()
            && $this->currentUser->getUserId() === $this->user->getUserId();
        if (!$isOwner) {
            return $this->json(['error' => Translation::get(key: 'err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $webAuthnKeys = $this->authWebAuthn->register(
                $register,
                $webAuthnUser->getWebAuthnKeys(),
                $webAuthnUser->getChallenge(),
            );
        } catch (Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $webAuthnUser->setWebAuthnKeys($webAuthnKeys);

        // Burn the registration challenge so the ceremony cannot be replayed against this session.
        $webAuthnUser->setChallenge('');
        $this->authWebAuthn->storeUserInSession($webAuthnUser);

        if ($this->user->setWebAuthnKeys($webAuthnUser->getWebAuthnKeys())) {
            $this->notifyOwnerAboutNewPasskey();

            return $this->json([
                'success' => 'ok',
                'message' => Translation::get(key: 'msgPasskeyRegistrationSuccess'),
            ], Response::HTTP_OK);
        }

        return $this->json(['error' => 'Cannot set WebAuthn keys'], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Finishes a passwordless sign-up: verifies the ceremony prepare() started and only then
     * creates the account, following the same policy as /api/register.
     *
     * A sign-up for a login or e-mail address that already belongs to an account is silently
     * discarded. The response is identical to a successful sign-up so that the endpoint cannot be
     * used as an oracle for account existence, and the existing account is never touched.
     */
    private function completeSignUp(string $register, WebAuthnUser $webAuthnUser): JsonResponse
    {
        if (!$this->configuration->get(item: 'security.enableRegistration')) {
            return $this->json(['error' => 'User registration is disabled.'], Response::HTTP_FORBIDDEN);
        }

        try {
            $webAuthnKeys = $this->authWebAuthn->register($register, '', $webAuthnUser->getChallenge());
        } catch (Exception $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        // Burn the registration challenge so the ceremony cannot be replayed against this session.
        $webAuthnUser->setChallenge('');
        $this->authWebAuthn->storeUserInSession($webAuthnUser);

        $username = $webAuthnUser->getName();
        $success = $this->json([
            'success' => 'ok',
            'message' => Translation::get(key: 'msgPasskeyRegistrationSuccess'),
        ], Response::HTTP_OK);

        try {
            $this->user->createUser($username);
        } catch (\Exception $e) {
            $isTaken = in_array(
                $e->getMessage(),
                [User::ERROR_USER_LOGIN_NOT_UNIQUE, User::ERROR_USER_EMAIL_NOT_UNIQUE],
                strict: true,
            );
            if (!$isTaken) {
                return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }

            $this->configuration
                ->getLogger()
                ->info(
                    message: 'Passkey sign-up discarded: e-mail address or login name already belongs to a registered user.',
                );

            return $success;
        }

        try {
            // The account starts blocked unless the administrator has turned manual activation off.
            $this->user->setStatus(status: 'blocked');
            $this->user->setAuthSource(AuthenticationSourceType::AUTH_WEB_AUTHN->value);
            $this->user->setUserData([
                'display_name' => $username,
                'email' => $username,
            ]);

            if (!$this->user->setWebAuthnKeys($webAuthnKeys)) {
                return $this->json(['error' => 'Cannot set WebAuthn keys'], Response::HTTP_BAD_REQUEST);
            }

            $isNowActive =
                !$this->configuration->get(item: 'spam.manualActivation') && $this->user->setStatus(status: 'active');
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $this->notifyAdminAboutNewAccount($username, $isNowActive);

        return $success;
    }

    /**
     * @throws \JsonException
     * @throws RandomException
     */
    #[Route(path: 'api/webauthn/prepare-login', name: 'api.private.webauthn.prepare-login', methods: ['POST'])]
    public function prepareLogin(Request $request): JsonResponse
    {
        if (!$this->configuration->get(item: 'security.enableWebAuthnSupport')) {
            return $this->json(['error' => 'WebAuthn support is disabled.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);
        $login = Filter::filterVar($data->username, FILTER_SANITIZE_SPECIAL_CHARS);

        // An unknown login gets the same decoy response as an account without a passkey. The
        // lookup result only decides whether there is anything to read from or write to the
        // database; it must not change the response, or the endpoint turns into a user oracle.
        $userExists = $this->user->getUserByLogin($login, false);
        $webAuthnKeys = $userExists ? $this->user->getWebAuthnKeys() : '';
        $hasStoredKeys = $webAuthnKeys !== '' && $webAuthnKeys !== '0';

        $publicKey = $this->authWebAuthn->prepareForLogin($webAuthnKeys, $login);

        // prepareForLogin() stamps a fresh single-use challenge onto the stored keys. It has to be
        // persisted, otherwise login() re-reads keys without a challenge and the replay check in
        // authenticate() has nothing to compare against. Without stored keys there is nothing to
        // persist, and an unauthenticated request must not write to the account at all.
        if ($hasStoredKeys) {
            $this->user->setWebAuthnKeys($webAuthnKeys);
        }

        return $this->json($publicKey, Response::HTTP_OK);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws \Exception
     */
    #[Route(path: 'api/webauthn/login', name: 'api.private.webauthn.login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        if (!$this->configuration->get(item: 'security.enableWebAuthnSupport')) {
            return $this->json(['error' => 'WebAuthn support is disabled.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);
        $login = Filter::filterVar($data->username, FILTER_SANITIZE_SPECIAL_CHARS);
        $loginData = $data->login;

        $this->user->getUserByLogin($login);

        $webAuthnKeys = $this->user->getWebAuthnKeys();

        $isAuthenticated = $this->authWebAuthn->authenticate($loginData, $webAuthnKeys);

        // authenticate() clears the challenge on the stored keys. Persisting it here is what makes
        // the challenge single-use and the assertion non-replayable.
        $this->user->setWebAuthnKeys($webAuthnKeys);

        if ($isAuthenticated) {
            $currentUser = $this->loginCurrentUser ?? new CurrentUser($this->configuration);
            $currentUser->getUserByLogin($login);

            if ($currentUser->isBlocked()) {
                return $this->json(['error' => Translation::get(key: 'ad_auth_fail')], Response::HTTP_UNAUTHORIZED);
            }

            // A passkey is sufficient as the sole factor only for passwordless accounts. If the
            // account has TOTP two-factor enabled, the passkey counts as the first factor only:
            // defer to the token step instead of granting the session, mirroring the password
            // login flow, so a passkey cannot bypass two-factor authentication.
            if ((int) $currentUser->getUserData('twofactor_enabled') === 1) {
                if ($currentUser->isTwoFactorLockedOut()) {
                    return $this->json(['error' => Translation::get(key: 'ad_auth_fail')], Response::HTTP_UNAUTHORIZED);
                }

                $session = $this->container->get(id: 'session');
                $session->set('2fa_pending_user_id', $currentUser->getUserId());
                // The WebAuthn login form has no remember-me option; the token step decides
                // cookie issuance, so carry an explicit "false" through it.
                $session->set('2fa_pending_remember_me', false);

                return $this->json([
                    'success' => 'ok',
                    'redirect' =>
                        $this->configuration->getDefaultUrl() . 'admin/token?user-id=' . $currentUser->getUserId(),
                ], Response::HTTP_OK);
            }

            $currentUser->setLoggedIn(loggedIn: true);
            $currentUser->setSuccess(success: true);
            $currentUser->updateSessionId(updateLastLogin: true);
            $currentUser->saveToSession();
            // Lets a passwordless account add another passkey shortly after signing in; see
            // requireEnrollmentStepUp().
            $this->container->get(id: 'session')->set(self::SESSION_PASSKEY_ASSERTED_AT, time());
            return $this->json([
                'success' => 'ok',
                'redirect' => $this->configuration->getDefaultUrl(),
            ], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get(key: 'ad_auth_fail')], Response::HTTP_UNAUTHORIZED);
    }
}
