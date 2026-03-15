<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Mail;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
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

    /**
     * @throws Exception
     */
    public function testUpdatePasswordWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UnauthorizedUserController();

        $this->expectException(\Exception::class);
        $controller->updatePassword($request);
    }

    /**
     * @throws Exception
     */
    public function testUpdatePasswordWithMissingUsernameReturnsConflict(): void
    {
        $requestData = json_encode([
            'email' => 'test@example.com',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UnauthorizedUserController();
        $response = $controller->updatePassword($request);

        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testUpdatePasswordWithMissingEmailReturnsConflict(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UnauthorizedUserController();
        $response = $controller->updatePassword($request);

        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testUpdatePasswordWithInvalidEmailReturnsConflict(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'email' => 'invalid-email',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UnauthorizedUserController();
        $response = $controller->updatePassword($request);

        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testUpdatePasswordWithEmptyUsernameReturnsConflict(): void
    {
        $requestData = json_encode([
            'username' => '',
            'email' => 'test@example.com',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UnauthorizedUserController();
        $response = $controller->updatePassword($request);

        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testUpdatePasswordWithNonExistentUserReturnsConflict(): void
    {
        $requestData = json_encode([
            'username' => 'nonexistentuser' . time(),
            'email' => 'test@example.com',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UnauthorizedUserController();
        $response = $controller->updatePassword($request);

        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testUpdatePasswordReturnsJsonResponse(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UnauthorizedUserController();
        $response = $controller->updatePassword($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testUpdatePasswordResponseHasCorrectContentType(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UnauthorizedUserController();
        $response = $controller->updatePassword($request);

        $this->assertTrue($response->headers->has('Content-Type'));
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }

    public function testJsonReturnsJsonResponse(): void
    {
        $controller = new UnauthorizedUserController();
        $response = $controller->json(['test' => 'data']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testJsonWithCustomStatusCode(): void
    {
        $controller = new UnauthorizedUserController();
        $response = $controller->json(['error' => 'test'], Response::HTTP_BAD_REQUEST);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testUpdatePasswordReturnsSuccessWhenPasswordResetMailIsSent(): void
    {
        $this->configuration->getAll();
        $configReflection = new \ReflectionClass(Configuration::class);
        $configProperty = $configReflection->getProperty('config');
        $currentConfig = $configProperty->getValue($this->configuration);
        $configProperty->setValue($this->configuration, array_merge($currentConfig, [
            'main.titleFAQ' => 'phpMyFAQ Test',
        ]));

        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserByLogin')->with('testuser')->willReturn(true);
        $user->expects($this->once())->method('getUserData')->with('email')->willReturn('test@example.com');
        $user->expects($this->once())->method('createPassword')->willReturn('NewPass123');
        $user->expects($this->once())->method('changePassword')->with('NewPass123')->willReturn(true);

        $mail = $this->createMock(Mail::class);
        $mail->expects($this->once())->method('addTo')->with('test@example.com');
        $mail->expects($this->once())->method('send');

        $controller = new UnauthorizedUserController(
            static fn(Configuration $configuration): CurrentUser => $user,
            static fn(Configuration $configuration): Mail => $mail,
        );

        $request = new Request([], [], [], [], [], [], json_encode([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->updatePassword($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(Translation::get('lostpwd_mail_okay'), $payload['success']);
    }

    /**
     * @throws Exception
     */
    public function testUpdatePasswordReturnsBadRequestWhenPasswordCreationFails(): void
    {
        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserByLogin')->with('testuser')->willReturn(true);
        $user->expects($this->once())->method('getUserData')->with('email')->willReturn('test@example.com');
        $user
            ->expects($this->once())
            ->method('createPassword')
            ->willThrowException(new \Exception('Cannot create password'));
        $user->expects($this->never())->method('changePassword');

        $controller = new UnauthorizedUserController(static fn(Configuration $configuration): CurrentUser => $user);

        $request = new Request([], [], [], [], [], [], json_encode([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->updatePassword($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('Cannot create password', $payload['error']);
    }

    /**
     * @throws Exception
     */
    public function testUpdatePasswordReturnsBadRequestWhenPasswordChangeFails(): void
    {
        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserByLogin')->with('testuser')->willReturn(true);
        $user->expects($this->once())->method('getUserData')->with('email')->willReturn('test@example.com');
        $user->expects($this->once())->method('createPassword')->willReturn('NewPass123');
        $user
            ->expects($this->once())
            ->method('changePassword')
            ->with('NewPass123')
            ->willThrowException(new \Exception('Cannot change password'));

        $controller = new UnauthorizedUserController(static fn(Configuration $configuration): CurrentUser => $user);

        $request = new Request([], [], [], [], [], [], json_encode([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->updatePassword($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('Cannot change password', $payload['error']);
    }

    /**
     * @throws Exception
     */
    public function testUpdatePasswordReturnsBadRequestWhenMailSendingFails(): void
    {
        $this->configuration->getAll();
        $configReflection = new \ReflectionClass(Configuration::class);
        $configProperty = $configReflection->getProperty('config');
        $currentConfig = $configProperty->getValue($this->configuration);
        $configProperty->setValue($this->configuration, array_merge($currentConfig, [
            'main.titleFAQ' => 'phpMyFAQ Test',
        ]));

        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserByLogin')->with('testuser')->willReturn(true);
        $user->expects($this->once())->method('getUserData')->with('email')->willReturn('test@example.com');
        $user->expects($this->once())->method('createPassword')->willReturn('NewPass123');
        $user->expects($this->once())->method('changePassword')->with('NewPass123')->willReturn(true);

        $mail = $this->createMock(Mail::class);
        $mail->expects($this->once())->method('addTo')->with('test@example.com');
        $mail
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new \phpMyFAQ\Core\Exception('SMTP failed'));

        $controller = new UnauthorizedUserController(
            static fn(Configuration $configuration): CurrentUser => $user,
            static fn(Configuration $configuration): Mail => $mail,
        );

        $request = new Request([], [], [], [], [], [], json_encode([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->updatePassword($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('SMTP failed', $payload['error']);
    }

    /**
     * @throws Exception
     */
    public function testUpdatePasswordReturnsConflictWhenEmailDoesNotMatchUser(): void
    {
        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserByLogin')->with('testuser')->willReturn(true);
        $user->expects($this->once())->method('getUserData')->with('email')->willReturn('other@example.com');
        $user->expects($this->never())->method('createPassword');

        $controller = new UnauthorizedUserController(static fn(Configuration $configuration): CurrentUser => $user);

        $request = new Request([], [], [], [], [], [], json_encode([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->updatePassword($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertSame(Translation::get('lostpwd_err_1'), $payload['error']);
    }

    /**
     * @throws Exception
     */
    public function testUpdatePasswordReturnsBadRequestWhenRecipientAddressCannotBeAdded(): void
    {
        $this->configuration->getAll();
        $configReflection = new \ReflectionClass(Configuration::class);
        $configProperty = $configReflection->getProperty('config');
        $currentConfig = $configProperty->getValue($this->configuration);
        $configProperty->setValue($this->configuration, array_merge($currentConfig, [
            'main.titleFAQ' => 'phpMyFAQ Test',
        ]));

        $user = $this->createMock(CurrentUser::class);
        $user->expects($this->once())->method('getUserByLogin')->with('testuser')->willReturn(true);
        $user->expects($this->once())->method('getUserData')->with('email')->willReturn('test@example.com');
        $user->expects($this->once())->method('createPassword')->willReturn('NewPass123');
        $user->expects($this->once())->method('changePassword')->with('NewPass123')->willReturn(true);

        $mail = $this->createMock(Mail::class);
        $mail
            ->expects($this->once())
            ->method('addTo')
            ->with('test@example.com')
            ->willThrowException(new \phpMyFAQ\Core\Exception('Invalid recipient'));
        $mail->expects($this->never())->method('send');

        $controller = new UnauthorizedUserController(
            static fn(Configuration $configuration): CurrentUser => $user,
            static fn(Configuration $configuration): Mail => $mail,
        );

        $request = new Request([], [], [], [], [], [], json_encode([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->updatePassword($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('Invalid recipient', $payload['error']);
    }
}
