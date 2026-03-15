<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Helper\RegistrationHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(RegistrationController::class)]
#[UsesNamespace('phpMyFAQ')]
class RegistrationControllerTest extends TestCase
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

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_X_PMF_TOKEN']);
        parent::tearDown();
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
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */ public function testCreateReturnsJsonResponse(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'fullname' => 'Test User',
            'email' => 'test@example.com',
            'is-visible' => 'false',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */ public function testCreateRequiresValidToken(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'fullname' => 'Test User',
            'email' => 'test@example.com',
            'is-visible' => 'false',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws \JsonException
     */ public function testCreateRequiresAllRequiredFields(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws \JsonException
     */ public function testCreateValidatesEmailFormat(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'fullname' => 'Test User',
            'email' => 'invalid-email',
            'is-visible' => 'false',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws \JsonException
     */ public function testCreateWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */ public function testCreateWithMissingUsername(): void
    {
        $requestData = json_encode([
            'fullname' => 'Test User',
            'email' => 'test@example.com',
            'is-visible' => 'false',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws \JsonException
     */ public function testCreateWithMissingFullname(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'is-visible' => 'false',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */ public function testCreateWithMissingEmail(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'fullname' => 'Test User',
            'is-visible' => 'false',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    public function testCreateWithMissingIsVisible(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'fullname' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */ public function testCreateWithEmptyUsername(): void
    {
        $requestData = json_encode([
            'username' => '',
            'fullname' => 'Test User',
            'email' => 'test@example.com',
            'is-visible' => 'false',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */ public function testCreateWithEmptyFullname(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'fullname' => '',
            'email' => 'test@example.com',
            'is-visible' => 'false',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws \JsonException
     */ public function testCreateWithEmptyEmail(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'fullname' => 'Test User',
            'email' => '',
            'is-visible' => 'false',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws \JsonException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testCreateReturnsCreatedWhenTokenAndPayloadAreValid(): void
    {
        $_SERVER['HTTP_X_PMF_TOKEN'] = (string) $this->configuration->get('api.apiClientToken');

        $helper = $this->createMock(RegistrationHelper::class);
        $helper->expects($this->once())->method('isDomainAllowed')->with('grace@example.org')->willReturn(true);
        $helper
            ->expects($this->once())
            ->method('createUser')
            ->with('grace', 'Grace Hopper', 'grace@example.org', false)
            ->willReturn(['registered' => true, 'success' => 'User created.']);

        $request = new Request([], [], [], [], [], [], json_encode([
            'username' => 'grace',
            'fullname' => 'Grace Hopper',
            'email' => 'grace@example.org',
            'is-visible' => false,
        ], JSON_THROW_ON_ERROR));

        $controller = new RegistrationController();
        $controller->setRegistrationHelperFactory(static fn() => $helper);

        $response = $controller->create($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertStringContainsString('"registered":true', (string) $response->getContent());
    }
}
