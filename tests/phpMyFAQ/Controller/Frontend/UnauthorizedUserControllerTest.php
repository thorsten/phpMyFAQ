<?php

/**
 * Frontend UnauthorizedUserController Test — password reset for external accounts.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Controller\Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-11-04
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Auth\AuthDatabase;
use phpMyFAQ\Auth\AuthException;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The public reset flow must only ever touch accounts whose password phpMyFAQ
 * owns. Accounts provisioned by LDAP, Entra ID, SSO, HTTP or WebAuthn carry a
 * random local hash, so a reset would plant a local password that bypasses the
 * external identity provider (CWE-640).
 */
class UnauthorizedUserControllerTest extends TestCase
{
    private Configuration $configuration;
    private UnauthorizedUserController $controller;

    /** @var User[] */
    private array $users = [];

    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);

        $reflection = new ReflectionClass(UnauthorizedUserController::class);
        $this->controller = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('configuration')->setValue($this->controller, $this->configuration);
    }

    protected function tearDown(): void
    {
        foreach ($this->users as $user) {
            $user->deleteUser();
        }
        $this->users = [];

        parent::tearDown();
    }

    private function provisionUser(string $login, string $authSource): User
    {
        $user = new User($this->configuration);
        // External auth drivers provision with an empty password, which createUser()
        // replaces with a random local hash.
        $this->assertTrue($user->createUser($login, ''));
        $this->assertTrue($user->setStatus('active'));
        $this->assertTrue($user->setUserData(['email' => $login . '@example.org']));
        $this->assertTrue($user->setAuthSource($authSource));
        $this->users[] = $user;

        $reloaded = new User($this->configuration);
        $this->assertTrue($reloaded->getUserByLogin($login));

        return $reloaded;
    }

    private function passwordHash(string $login): string
    {
        $db = $this->configuration->getDb();
        $result = $db->query(sprintf(
            "SELECT pass FROM %sfaquserlogin WHERE login = '%s'",
            Database::getTablePrefix(),
            $db->escape($login),
        ));

        return (string) $db->fetchArray($result)['pass'];
    }

    private function credentialsValid(string $login, #[\SensitiveParameter] string $password): bool
    {
        $auth = new AuthDatabase($this->configuration);
        $auth->getEncryptionContainer(PMF_ENCRYPTION_TYPE);

        try {
            return $auth->checkCredentials($login, $password);
        } catch (AuthException) {
            return false;
        }
    }

    private function signedResetRequest(User $user, #[\SensitiveParameter] string $password): Request
    {
        $expires = time() + 600;
        $payload = $user->getUserId() . '|' . $expires . '|' . $this->passwordHash($user->getLogin());
        $signature = hash_hmac('sha256', $payload, (string) $this->configuration->get('main.phpMyFAQToken'));

        return Request::create('/api/user/password/reset', 'POST', [], [], [], [], json_encode([
            'u' => $user->getUserId(),
            'exp' => $expires,
            'sig' => $signature,
            'password' => $password,
            'password_repeat' => $password,
        ]));
    }

    public function testResetRefusesValidTokenForExternalAccount(): void
    {
        $user = $this->provisionUser('reset-ldap-user', AuthenticationSourceType::AUTH_LDAP->value);
        $hashBefore = $this->passwordHash($user->getLogin());

        $response = $this->controller->resetPassword($this->signedResetRequest($user, 'Attacker123!'));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame($hashBefore, $this->passwordHash($user->getLogin()));
        $this->assertFalse($this->credentialsValid($user->getLogin(), 'Attacker123!'));
    }

    public function testResetAcceptsValidTokenForLocalAccount(): void
    {
        $user = $this->provisionUser('reset-local-user', AuthenticationSourceType::AUTH_LOCAL->value);

        $response = $this->controller->resetPassword($this->signedResetRequest($user, 'NewLocal123!'));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue($this->credentialsValid($user->getLogin(), 'NewLocal123!'));
    }

    /**
     * A passkey is a password-independent credential. One planted through a hijacked session
     * must not survive the password reset meant to lock the intruder out.
     */
    public function testResetRevokesPasskeysOfLocalAccount(): void
    {
        $user = $this->provisionUser('reset-passkey-user', AuthenticationSourceType::AUTH_LOCAL->value);
        $this->assertTrue($user->setWebAuthnKeys('[{"key":"PLANTED","id":[9,9,9]}]'));
        $this->assertSame('[{"key":"PLANTED","id":[9,9,9]}]', $user->getWebAuthnKeys());

        $response = $this->controller->resetPassword($this->signedResetRequest($user, 'NewLocal123!'));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('', $user->getWebAuthnKeys());
    }

    public function testUpdatePasswordDoesNotIssueTokenForExternalAccount(): void
    {
        $user = $this->provisionUser('reset-sso-user', AuthenticationSourceType::AUTH_SSO->value);

        $request = Request::create('/api/user/password/update', 'PUT', [], [], [], [], json_encode([
            'username' => $user->getLogin(),
            'email' => $user->getUserData('email'),
        ]));

        // No mailer is configured in tests, so reaching the mail step emits PHP
        // warnings. Turn them into a failure: the auth-source guard must return
        // the generic success before any token is built or mailed.
        set_error_handler(static function (int $severity, string $message): never {
            throw new \RuntimeException('Reset mail was attempted: ' . $message);
        });
        try {
            $response = $this->controller->updatePassword($request);
        } finally {
            restore_error_handler();
        }

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(
            ['success' => Translation::get(key: 'lostpwd_mail_okay')],
            json_decode((string) $response->getContent(), true),
        );
    }
}
