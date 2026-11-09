<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\Http\RateLimiter;
use phpMyFAQ\Kernel;
use phpMyFAQ\Mail;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\PasswordResetTokenService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(UnauthorizedUserController::class)]
#[UsesNamespace('phpMyFAQ')]
class UnauthorizedUserControllerTest extends TestCase
{
    private Configuration $configuration;
    private Sqlite3 $dbHandle;
    private string $databasePath;
    private ?Configuration $previousConfiguration = null;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();
        $configurationProperty->setValue(null, null);

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-unauthorized-user-controller-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->configuration = new Configuration($this->dbHandle);

        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $this->dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');
        Database::setTablePrefix('');
    }

    protected function tearDown(): void
    {
        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        $this->dbHandle->close();
        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, null);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, '');
        @unlink($this->databasePath);

        parent::tearDown();
    }

    private function makeController(
        ?CurrentUser $user = null,
        ?Mail $mail = null,
        ?PasswordResetTokenService $tokenService = null,
    ): UnauthorizedUserController {
        return new UnauthorizedUserController(
            $user === null ? null : static fn(Configuration $c): CurrentUser => $user,
            $mail === null ? null : static fn(Configuration $c): Mail => $mail,
            $tokenService,
            new RateLimiter(),
            $this->configuration,
        );
    }

    private function jsonRequest(array $data): Request
    {
        return new Request([], [], [], [], [], [], json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * A token as the service would have mailed it.
     *
     * @return array{expires: int, signature: string}
     */
    private function outstandingToken(?int $expires = null): array
    {
        return ['expires' => $expires ?? time() + 600, 'signature' => bin2hex(random_bytes(32))];
    }

    /**
     * What the faquser row holds for an outstanding token: the hash, never the token.
     *
     * @param array{expires: int, signature: string} $token
     * @return array{hash: string, expires: int}
     */
    private function storedTokenFor(array $token): array
    {
        return ['hash' => hash('sha256', $token['signature']), 'expires' => $token['expires']];
    }

    /**
     * @throws Exception
     */
    public function testRequestResetReturnsGenericSuccessOnInvalidJson(): void
    {
        $controller = $this->makeController();
        $response = $controller->requestReset(new Request([], [], [], [], [], [], 'invalid json'));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(Translation::get('lostpwd_mail_okay'), $payload['success']);
    }

    /**
     * @throws Exception
     */
    public function testRequestResetReturnsGenericSuccessOnMissingFields(): void
    {
        $controller = $this->makeController();
        $response = $controller->requestReset($this->jsonRequest(['username' => '', 'email' => '']));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testRequestResetDoesNotLeakWhenUserMissing(): void
    {
        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserByLogin')->willReturn(false);
        $user->expects($this->never())->method('getEncryptedPassword');

        $controller = $this->makeController($user);
        $response = $controller->requestReset($this->jsonRequest([
            'username' => 'ghost',
            'email' => 'ghost@example.com',
        ]));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(Translation::get('lostpwd_mail_okay'), $payload['success']);
    }

    /**
     * @throws Exception
     */
    public function testRequestResetDoesNotLeakWhenEmailMismatch(): void
    {
        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserByLogin')->willReturn(true);
        $user->expects($this->once())->method('getUserData')->with('email')->willReturn('real@example.com');
        $user->expects($this->never())->method('getEncryptedPassword');

        $controller = $this->makeController($user);
        $response = $controller->requestReset($this->jsonRequest([
            'username' => 'someone',
            'email' => 'attacker@example.com',
        ]));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testRequestResetSilentlySkipsForNonDbAuthSource(): void
    {
        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserByLogin')->willReturn(true);
        $user->expects($this->once())->method('getUserData')->with('email')->willReturn('real@example.com');
        $user->expects($this->once())->method('getEncryptedPassword')->willReturn('');
        $user->expects($this->never())->method('getUserId');

        $mail = $this->createMock(Mail::class);
        $mail->expects($this->never())->method('send');

        $controller = $this->makeController($user, $mail);
        $response = $controller->requestReset($this->jsonRequest([
            'username' => 'ldap-user',
            'email' => 'real@example.com',
        ]));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testRequestResetSendsSignedLinkEmail(): void
    {
        $configReflection = new \ReflectionClass(Configuration::class);
        $configProperty = $configReflection->getProperty('config');
        $configProperty->setValue($this->configuration, array_merge(
            $configProperty->getValue($this->configuration) ?? [],
            ['main.titleFAQ' => 'phpMyFAQ Test', 'main.referenceURL' => 'https://faq.test'],
        ));

        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserByLogin')->willReturn(true);
        $user->expects($this->once())->method('getUserData')->with('email')->willReturn('real@example.com');
        $user->expects($this->once())->method('getEncryptedPassword')->willReturn('hashed-pw-123');
        $user->method('getUserId')->willReturn(42);
        $user->expects($this->once())->method('storePasswordResetToken')->willReturn(true);
        $user->expects($this->never())->method('changePassword');
        $user->expects($this->never())->method('createPassword');

        $capturedMessage = null;
        $mail = $this->createMock(Mail::class);
        $mail->expects($this->once())->method('addTo')->with('real@example.com');
        $mail
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function () use ($mail, &$capturedMessage): int {
                $capturedMessage = $mail->message;
                return 1;
            });

        $controller = $this->makeController($user, $mail);
        $response = $controller->requestReset($this->jsonRequest([
            'username' => 'realuser',
            'email' => 'real@example.com',
        ]));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertNotNull($capturedMessage);
        $this->assertStringContainsString('/user/reset-password?u=42&exp=', $capturedMessage);
        $this->assertStringContainsString('&sig=', $capturedMessage);
        $this->assertStringNotContainsString('hashed-pw-123', $capturedMessage);
    }

    /**
     * Without a stored token hash no link can ever be verified, so none is mailed. The
     * response stays the generic success so the failure does not leak account existence.
     *
     * @throws Exception
     */
    public function testRequestResetReturnsGenericSuccessWhenTokenCannotBeStored(): void
    {
        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserByLogin')->willReturn(true);
        $user->expects($this->once())->method('getUserData')->with('email')->willReturn('real@example.com');
        $user->expects($this->once())->method('getEncryptedPassword')->willReturn('hashed-pw-123');
        $user->method('getUserId')->willReturn(42);
        $user->expects($this->once())->method('storePasswordResetToken')->willReturn(false);

        $mail = $this->createMock(Mail::class);
        $mail->expects($this->never())->method('send');

        $controller = $this->makeController($user, $mail);
        $response = $controller->requestReset($this->jsonRequest([
            'username' => 'realuser',
            'email' => 'real@example.com',
        ]));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(Translation::get('lostpwd_mail_okay'), $payload['success']);
    }

    /**
     * A failing mail delivery must be swallowed: the response stays the generic success so the
     * caller cannot tell an existing account from a missing one.
     *
     * @throws Exception
     */
    public function testRequestResetReturnsGenericSuccessWhenMailDeliveryFails(): void
    {
        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserByLogin')->willReturn(true);
        $user->expects($this->once())->method('getUserData')->with('email')->willReturn('real@example.com');
        $user->expects($this->once())->method('getEncryptedPassword')->willReturn('hashed-pw-123');
        $user->method('getUserId')->willReturn(42);
        $user->method('storePasswordResetToken')->willReturn(true);

        $mail = $this->createMock(Mail::class);
        $mail->expects($this->once())->method('addTo')->with('real@example.com');
        $mail->expects($this->once())->method('send')->willThrowException(new Exception('SMTP unavailable'));

        $controller = $this->makeController($user, $mail);
        $response = $controller->requestReset($this->jsonRequest([
            'username' => 'realuser',
            'email' => 'real@example.com',
        ]));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(Translation::get('lostpwd_mail_okay'), $payload['success']);
    }

    /**
     * @throws Exception
     */
    public function testResetRejectsJsonBodyThatIsNotAnObject(): void
    {
        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->never())->method('getUserById');

        $controller = $this->makeController($user);
        $response = $controller->reset(new Request([], [], [], [], [], [], '[7, "NewSecret123"]'));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(Translation::get('resetpwd_err_invalid'), $payload['error']);
    }

    /**
     * @throws Exception
     */
    public function testResetRejectsUserWithoutStoredPassword(): void
    {
        $token = $this->outstandingToken();

        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserById')->willReturn(true);
        $user->expects($this->once())->method('getEncryptedPassword')->willReturn('');
        $user->expects($this->never())->method('getPasswordResetToken');
        $user->expects($this->never())->method('changePassword');

        $controller = $this->makeController($user);
        $response = $controller->reset($this->jsonRequest([
            'u' => 7,
            'exp' => $token['expires'],
            'sig' => $token['signature'],
            'password' => 'NewSecret123',
            'password_repeat' => 'NewSecret123',
        ]));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(Translation::get('resetpwd_err_invalid'), $payload['error']);
    }

    /**
     * @throws Exception
     */
    public function testResetReportsFailureWhenPasswordCannotBeChanged(): void
    {
        $token = $this->outstandingToken();

        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserById')->with(7, true)->willReturn(true);
        $user->expects($this->once())->method('getEncryptedPassword')->willReturn('pw-key');
        $user->expects($this->once())->method('getPasswordResetToken')->willReturn($this->storedTokenFor($token));
        $user->expects($this->once())->method('changePassword')->with('NewSecret123')->willReturn(false);
        $user->expects($this->never())->method('revokePasskeys');

        $controller = $this->makeController($user);
        $response = $controller->reset($this->jsonRequest([
            'u' => 7,
            'exp' => $token['expires'],
            'sig' => $token['signature'],
            'password' => 'NewSecret123',
            'password_repeat' => 'NewSecret123',
        ]));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(Translation::get('ad_passwd_fail'), $payload['error']);
    }

    /**
     * @throws Exception
     */
    public function testResetRejectsTamperedSignature(): void
    {
        $token = $this->outstandingToken();

        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserById')->with(7, true)->willReturn(true);
        $user->expects($this->once())->method('getEncryptedPassword')->willReturn('pw-key');
        $user->expects($this->once())->method('getPasswordResetToken')->willReturn($this->storedTokenFor($token));
        $user->expects($this->never())->method('changePassword');

        $controller = $this->makeController($user);
        $response = $controller->reset($this->jsonRequest([
            'u' => 7,
            'exp' => $token['expires'],
            'sig' => str_repeat('0', strlen($token['signature'])),
            'password' => 'NewSecret123',
            'password_repeat' => 'NewSecret123',
        ]));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(Translation::get('resetpwd_err_invalid'), $payload['error']);
    }

    /**
     * @throws Exception
     */
    public function testResetRejectsExpiredToken(): void
    {
        $token = $this->outstandingToken(time() - 60);

        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserById')->willReturn(true);
        $user->expects($this->once())->method('getEncryptedPassword')->willReturn('pw-key');
        $user->method('getPasswordResetToken')->willReturn($this->storedTokenFor($token));
        $user->expects($this->never())->method('changePassword');

        $controller = $this->makeController($user);
        $response = $controller->reset($this->jsonRequest([
            'u' => 7,
            'exp' => $token['expires'],
            'sig' => $token['signature'],
            'password' => 'NewSecret123',
            'password_repeat' => 'NewSecret123',
        ]));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testResetRejectsReplayAfterPasswordChange(): void
    {
        $token = $this->outstandingToken();

        // The password change cleared the stored token hash, so the mailed link is void.
        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserById')->willReturn(true);
        $user->expects($this->once())->method('getEncryptedPassword')->willReturn('new-key');
        $user->expects($this->once())->method('getPasswordResetToken')->willReturn(['hash' => '', 'expires' => 0]);
        $user->expects($this->never())->method('changePassword');

        $controller = $this->makeController($user);
        $response = $controller->reset($this->jsonRequest([
            'u' => 7,
            'exp' => $token['expires'],
            'sig' => $token['signature'],
            'password' => 'NewSecret123',
            'password_repeat' => 'NewSecret123',
        ]));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testResetSucceedsWithValidToken(): void
    {
        $token = $this->outstandingToken();

        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserById')->with(7, true)->willReturn(true);
        $user->expects($this->once())->method('getEncryptedPassword')->willReturn('pw-key');
        $user->expects($this->once())->method('getPasswordResetToken')->willReturn($this->storedTokenFor($token));
        $user->expects($this->once())->method('changePassword')->with('NewSecret123')->willReturn(true);
        // The reset proves the mailbox, not the old credentials: passkeys planted by whoever
        // held the account before must not survive it.
        $user->expects($this->once())->method('revokePasskeys')->willReturn(true);

        $controller = $this->makeController($user);
        $response = $controller->reset($this->jsonRequest([
            'u' => 7,
            'exp' => $token['expires'],
            'sig' => $token['signature'],
            'password' => 'NewSecret123',
            'password_repeat' => 'NewSecret123',
        ]));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(Translation::get('resetpwd_success'), $payload['success']);
    }

    /**
     * @throws Exception
     */
    public function testResetRejectsShortPassword(): void
    {
        $controller = $this->makeController();
        $response = $controller->reset($this->jsonRequest([
            'u' => 7,
            'exp' => time() + 600,
            'sig' => 'whatever',
            'password' => 'short',
            'password_repeat' => 'short',
        ]));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(Translation::get('msgPasswordTooShort'), $payload['error']);
    }

    /**
     * @throws Exception
     */
    public function testResetRejectsMismatchedPasswords(): void
    {
        $controller = $this->makeController();
        $response = $controller->reset($this->jsonRequest([
            'u' => 7,
            'exp' => time() + 600,
            'sig' => 'whatever',
            'password' => 'LongEnough123',
            'password_repeat' => 'OtherPassword456',
        ]));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(Translation::get('ad_passwd_fail'), $payload['error']);
    }

    /**
     * @throws Exception
     */
    public function testResetRejectsMissingTokenFields(): void
    {
        $controller = $this->makeController();
        $response = $controller->reset($this->jsonRequest([
            'password' => 'NewSecret123',
            'password_repeat' => 'NewSecret123',
        ]));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testResetRejectsForUnknownUser(): void
    {
        $token = $this->outstandingToken();

        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserById')->willReturn(false);
        $user->expects($this->never())->method('changePassword');

        $controller = $this->makeController($user);
        $response = $controller->reset($this->jsonRequest([
            'u' => 7,
            'exp' => $token['expires'],
            'sig' => $token['signature'],
            'password' => 'NewSecret123',
            'password_repeat' => 'NewSecret123',
        ]));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * The controller must be resolved from the container: without a registration it is
     * instantiated with `new`, which leaves it with an in-memory rate limiter that forgets
     * every request as soon as the request ends.
     */
    public function testControllerIsRegisteredWithTheSharedRateLimiter(): void
    {
        $kernel = new Kernel(routingContext: 'public', debug: true);
        $buildMethod = new \ReflectionMethod(Kernel::class, 'buildContainer');
        $container = $buildMethod->invoke($kernel);

        $this->assertInstanceOf(ContainerBuilder::class, $container);
        $this->assertTrue($container->hasDefinition(UnauthorizedUserController::class));

        $arguments = $container->getDefinition(UnauthorizedUserController::class)->getArguments();
        $this->assertInstanceOf(Reference::class, $arguments[3]);
        $this->assertSame('phpmyfaq.http.rate-limiter', (string) $arguments[3]);
        $this->assertInstanceOf(Reference::class, $arguments[4]);
        $this->assertSame('phpmyfaq.configuration', (string) $arguments[4]);
    }

    public function testJsonReturnsJsonResponse(): void
    {
        $controller = $this->makeController();
        $response = $controller->json(['test' => 'data']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * Regression: an account provisioned by an external identity provider (OIDC here) gets a
     * random local hash from createUser(). The reset flow must neither mail a token for such an
     * account nor accept a token forged from that hash, otherwise anyone reading the mailbox
     * could give the account a local password and bypass the identity provider entirely.
     *
     * @throws Exception
     */
    public function testResetFlowRefusesExternallyProvisionedAccount(): void
    {
        $provisioned = new CurrentUser($this->configuration);
        $this->assertTrue($provisioned->createUser('oidc-alice', ''));
        $this->assertTrue($provisioned->setUserData(['email' => 'alice@corp.example']));
        $this->assertTrue($provisioned->setAuthSource(AuthenticationSourceType::AUTH_OIDC->value));
        $userId = $provisioned->getUserId();

        $db = $this->configuration->getDb();
        $row = $db->fetchArray($db->query("SELECT pass FROM faquserlogin WHERE login = 'oidc-alice'"));
        $this->assertIsArray($row);
        $storedHash = (string) $row['pass'];
        $this->assertNotSame('', $storedHash);

        $mail = $this->createMock(Mail::class);
        $mail->expects($this->never())->method('send');

        $controller = $this->makeController(new CurrentUser($this->configuration), $mail);

        $issue = $controller->requestReset($this->jsonRequest([
            'username' => 'oidc-alice',
            'email' => 'alice@corp.example',
        ]));
        $this->assertSame(Response::HTTP_OK, $issue->getStatusCode());

        $forged = $this->tokenForgedFromPasswordHash($userId, $storedHash);
        $reset = $controller->reset($this->jsonRequest([
            'u' => $userId,
            'exp' => $forged['expires'],
            'sig' => $forged['signature'],
            'password' => 'Attacker123!',
            'password_repeat' => 'Attacker123!',
        ]));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $reset->getStatusCode());
        $payload = json_decode((string) $reset->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(Translation::get('resetpwd_err_invalid'), $payload['error']);

        $after = $db->fetchArray($db->query("SELECT pass FROM faquserlogin WHERE login = 'oidc-alice'"));
        $this->assertIsArray($after);
        $this->assertSame($storedHash, $after['pass']);
    }

    /**
     * Regression: the reset token used to be an HMAC keyed by the stored password hash, so a
     * data backup or any database read handed out a valid reset link for every account. Neither
     * that forgery nor the token hash now stored in the database may pass; only the mailed
     * token does, and only once.
     *
     * @throws Exception
     */
    public function testResetLinkCannotBeForgedFromDatabaseContents(): void
    {
        $account = new CurrentUser($this->configuration);
        $this->assertTrue($account->createUser('local-bob', 'OldSecret123'));
        $this->assertTrue($account->setUserData(['email' => 'bob@example.com']));
        $userId = $account->getUserId();

        $db = $this->configuration->getDb();
        $row = $db->fetchArray($db->query("SELECT pass FROM faquserlogin WHERE login = 'local-bob'"));
        $this->assertIsArray($row);
        $storedHash = (string) $row['pass'];

        $capturedMessage = null;
        $mail = $this->createMock(Mail::class);
        $mail
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function () use ($mail, &$capturedMessage): int {
                $capturedMessage = $mail->message;
                return 1;
            });

        $controller = $this->makeController(new CurrentUser($this->configuration), $mail);
        $issue = $controller->requestReset($this->jsonRequest([
            'username' => 'local-bob',
            'email' => 'bob@example.com',
        ]));
        $this->assertSame(Response::HTTP_OK, $issue->getStatusCode());
        $this->assertNotNull($capturedMessage);
        $this->assertSame(1, preg_match('/[?&]exp=(\d+)&sig=([0-9a-f]{64})/', $capturedMessage, $link));
        $mailed = ['expires' => (int) $link[1], 'signature' => $link[2]];

        $stored = $db->fetchArray($db->query(sprintf(
            'SELECT password_reset_token, password_reset_expires FROM faquser WHERE user_id = %d',
            $userId,
        )));
        $this->assertIsArray($stored);
        $this->assertSame($mailed['expires'], (int) $stored['password_reset_expires']);
        $this->assertSame(hash('sha256', $mailed['signature']), $stored['password_reset_token']);

        $attempt = fn(array $token, string $password): Response => $controller->reset($this->jsonRequest([
            'u' => $userId,
            'exp' => $token['expires'],
            'sig' => $token['signature'],
            'password' => $password,
            'password_repeat' => $password,
        ]));

        $forged = $this->tokenForgedFromPasswordHash($userId, $storedHash);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $attempt($forged, 'Attacker123!')->getStatusCode());

        $fromDatabase = ['expires' => $mailed['expires'], 'signature' => (string) $stored['password_reset_token']];
        $this->assertSame(Response::HTTP_BAD_REQUEST, $attempt($fromDatabase, 'Attacker123!')->getStatusCode());

        $unchanged = $db->fetchArray($db->query("SELECT pass FROM faquserlogin WHERE login = 'local-bob'"));
        $this->assertIsArray($unchanged);
        $this->assertSame($storedHash, $unchanged['pass']);

        $this->assertSame(Response::HTTP_OK, $attempt($mailed, 'NewSecret123')->getStatusCode());

        $changed = $db->fetchArray($db->query("SELECT pass FROM faquserlogin WHERE login = 'local-bob'"));
        $this->assertIsArray($changed);
        $this->assertNotSame($storedHash, $changed['pass']);

        $cleared = $db->fetchArray($db->query(sprintf(
            'SELECT password_reset_token, password_reset_expires FROM faquser WHERE user_id = %d',
            $userId,
        )));
        $this->assertIsArray($cleared);
        $this->assertNull($cleared['password_reset_token']);
        $this->assertNull($cleared['password_reset_expires']);

        // The link is single-use: replaying it after the reset must fail.
        $this->assertSame(Response::HTTP_BAD_REQUEST, $attempt($mailed, 'Replayed123!')->getStatusCode());
    }

    /**
     * The token as the vulnerable 4.2.0 pre-releases computed it: an HMAC over "{userId}|{expires}"
     * keyed by the stored password hash.
     *
     * @return array{expires: int, signature: string}
     */
    private function tokenForgedFromPasswordHash(int $userId, string $storedHash): array
    {
        $expires = time() + 600;

        return ['expires' => $expires, 'signature' => hash_hmac('sha256', $userId . '|' . $expires, $storedHash)];
    }
}
