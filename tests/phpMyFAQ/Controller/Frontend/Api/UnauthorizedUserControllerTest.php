<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
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
}
