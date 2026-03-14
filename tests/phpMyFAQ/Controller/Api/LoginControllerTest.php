<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class LoginControllerTest extends TestCase
{
    private Configuration $configuration;

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

        $this->configuration = $this->createConfiguration();
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);
    }

    private function createConfiguration(): Configuration
    {
        try {
            return Configuration::getConfigurationInstance();
        } catch (\TypeError) {
            $db = new Sqlite3();
            $db->connect(PMF_TEST_DIR . '/test.db', '', '');
            $configuration = new Configuration($db);

            $configurationReflection = new \ReflectionClass(Configuration::class);
            $configurationProperty = $configurationReflection->getProperty('configuration');
            $configurationProperty->setValue(null, $configuration);

            return $configuration;
        }
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */ public function testLoginReturnsJsonResponse(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'password' => 'testpassword',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new LoginController();
        $response = $controller->login($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */ public function testLoginReturnsCorrectStatusCodeOnFailure(): void
    {
        $requestData = json_encode([
            'username' => 'invaliduser',
            'password' => 'invalidpassword',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new LoginController();
        $response = $controller->login($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */ public function testLoginResponseContainsLoggedInField(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'password' => 'testpassword',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new LoginController();
        $response = $controller->login($request);

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('loggedin', $content);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */ public function testLoginFailureContainsErrorField(): void
    {
        $requestData = json_encode([
            'username' => 'invaliduser',
            'password' => 'invalidpassword',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new LoginController();
        $response = $controller->login($request);

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);
        $this->assertFalse($content['loggedin']);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testLoginWithEmptyUsername(): void
    {
        $requestData = json_encode([
            'username' => '',
            'password' => 'testpassword',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new LoginController();
        $response = $controller->login($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testLoginWithEmptyPassword(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'password' => '',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new LoginController();
        $response = $controller->login($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testLoginWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new LoginController();

        $this->expectException(\JsonException::class);
        $controller->login($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testLoginResponseIsValidJson(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'password' => 'testpassword',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new LoginController();
        $response = $controller->login($request);

        $this->assertJson($response->getContent());
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testLoginWithSpecialCharactersInUsername(): void
    {
        $requestData = json_encode([
            'username' => 'test@user#123',
            'password' => 'testpassword',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new LoginController();
        $response = $controller->login($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertContains($response->getStatusCode(), [200, 400]);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testLoginSucceedsWithValidCredentials(): void
    {
        $requestData = json_encode([
            'username' => 'admin',
            'password' => 'password',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new LoginController();
        $response = $controller->login($request);

        $content = json_decode((string) $response->getContent(), true);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(['loggedin' => true], $content);
    }
}
