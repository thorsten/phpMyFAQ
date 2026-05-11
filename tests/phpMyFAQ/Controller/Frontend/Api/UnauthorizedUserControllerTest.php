<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Http\RateLimiter;
use phpMyFAQ\Mail;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\PasswordResetTokenService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
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
        $user->expects($this->once())->method('getUserId')->willReturn(42);
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
     * @throws Exception
     */
    public function testResetRejectsTamperedSignature(): void
    {
        $tokenService = new PasswordResetTokenService();
        $token = $tokenService->issue(7, 'pw-key');

        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserById')->with(7, true)->willReturn(true);
        $user->expects($this->once())->method('getEncryptedPassword')->willReturn('pw-key');
        $user->expects($this->never())->method('changePassword');

        $controller = $this->makeController($user, null, $tokenService);
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
        $tokenService = new PasswordResetTokenService();
        $expired = time() - 60;
        $signature = hash_hmac('sha256', '7|' . $expired, 'pw-key');

        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserById')->willReturn(true);
        $user->expects($this->once())->method('getEncryptedPassword')->willReturn('pw-key');
        $user->expects($this->never())->method('changePassword');

        $controller = $this->makeController($user, null, $tokenService);
        $response = $controller->reset($this->jsonRequest([
            'u' => 7,
            'exp' => $expired,
            'sig' => $signature,
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
        $tokenService = new PasswordResetTokenService();
        $token = $tokenService->issue(7, 'old-key');

        // Simulate the user having changed their password since issuance: signing key differs.
        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserById')->willReturn(true);
        $user->expects($this->once())->method('getEncryptedPassword')->willReturn('new-key');
        $user->expects($this->never())->method('changePassword');

        $controller = $this->makeController($user, null, $tokenService);
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
        $tokenService = new PasswordResetTokenService();
        $token = $tokenService->issue(7, 'pw-key');

        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserById')->with(7, true)->willReturn(true);
        $user->expects($this->once())->method('getEncryptedPassword')->willReturn('pw-key');
        $user->expects($this->once())->method('changePassword')->with('NewSecret123')->willReturn(true);

        $controller = $this->makeController($user, null, $tokenService);
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
        $tokenService = new PasswordResetTokenService();
        $token = $tokenService->issue(7, 'pw-key');

        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserById')->willReturn(false);
        $user->expects($this->never())->method('changePassword');

        $controller = $this->makeController($user, null, $tokenService);
        $response = $controller->reset($this->jsonRequest([
            'u' => 7,
            'exp' => $token['expires'],
            'sig' => $token['signature'],
            'password' => 'NewSecret123',
            'password_repeat' => 'NewSecret123',
        ]));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testJsonReturnsJsonResponse(): void
    {
        $controller = $this->makeController();
        $response = $controller->json(['test' => 'data']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }
}
