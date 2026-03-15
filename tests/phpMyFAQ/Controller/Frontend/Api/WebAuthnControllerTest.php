<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(WebAuthnController::class)]
#[UsesNamespace('phpMyFAQ')]
class WebAuthnControllerTest extends TestCase
{
    private Configuration $configuration;

    private string $originalWebAuthnValue;

    private string $originalRegistrationValue;

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

        $this->configuration = Configuration::getConfigurationInstance();

        // Save original values
        $configArray = $this->getConfigArray();
        $this->originalWebAuthnValue = $configArray['security.enableWebAuthnSupport'] ?? 'false';
        $this->originalRegistrationValue = $configArray['security.enableRegistration'] ?? 'true';
    }

    protected function tearDown(): void
    {
        // Restore original config values in-memory (no DB writes)
        $this->setInMemoryConfig('security.enableWebAuthnSupport', $this->originalWebAuthnValue);
        $this->setInMemoryConfig('security.enableRegistration', $this->originalRegistrationValue);

        parent::tearDown();
    }

    /**
     * Sets a config value directly in the in-memory config array using Reflection.
     */
    private function setInMemoryConfig(string $key, string $value): void
    {
        $reflection = new ReflectionClass($this->configuration);
        $property = $reflection->getProperty('config');
        $config = $property->getValue($this->configuration);
        $config[$key] = $value;
        $property->setValue($this->configuration, $config);
    }

    /**
     * Gets the in-memory config array using Reflection.
     */
    private function getConfigArray(): array
    {
        $reflection = new ReflectionClass($this->configuration);
        $property = $reflection->getProperty('config');
        return $property->getValue($this->configuration);
    }

    public function testPrepareReturnsForbiddenWhenWebAuthnDisabled(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'false');

        $requestData = json_encode(['username' => 'testuser']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $response = $controller->prepare($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testRegisterReturnsForbiddenWhenWebAuthnDisabled(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'false');

        $requestData = json_encode(['register' => 'test-data']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $response = $controller->register($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testPrepareLoginReturnsForbiddenWhenWebAuthnDisabled(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'false');

        $requestData = json_encode(['username' => 'testuser']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $response = $controller->prepareLogin($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testLoginReturnsForbiddenWhenWebAuthnDisabled(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'false');

        $requestData = json_encode(['username' => 'testuser', 'login' => 'test-data']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $response = $controller->login($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    /**
     * @throws \JsonException
     * @throws \Exception
     */
    public function testPrepareWithInvalidJsonThrowsException(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $this->expectException(\Exception::class);
        $controller->prepare($request);
    }

    /**
     * @throws \JsonException
     * @throws Exception
     * @throws \Exception
     */
    public function testPrepareWithMissingUsernameThrowsException(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $requestData = json_encode([]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $this->expectException(\Exception::class);
        $controller->prepare($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testRegisterWithInvalidJsonThrowsException(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $this->expectException(\Exception::class);
        $controller->register($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testRegisterWithMissingRegisterDataThrowsException(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $requestData = json_encode([]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $this->expectException(\Exception::class);
        $controller->register($request);
    }

    /**
     * @throws \JsonException
     * @throws \Exception
     */
    public function testPrepareLoginWithInvalidJsonThrowsException(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $this->expectException(\Exception::class);
        $controller->prepareLogin($request);
    }

    /**
     * @throws \JsonException
     * @throws \Exception
     */
    public function testPrepareLoginWithMissingUsernameThrowsException(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $requestData = json_encode([]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $this->expectException(\Exception::class);
        $controller->prepareLogin($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws \Exception
     */
    public function testLoginWithInvalidJsonThrowsException(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $this->expectException(\Exception::class);
        $controller->login($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws \Exception
     */
    public function testLoginWithMissingUsernameThrowsException(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $requestData = json_encode([
            'login' => 'test-data',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $this->expectException(\Exception::class);
        $controller->login($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     * @throws \Exception
     */
    public function testLoginWithMissingLoginDataThrowsException(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');

        $requestData = json_encode([
            'username' => 'testuser',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $this->expectException(\Exception::class);
        $controller->login($request);
    }

    public function testPrepareReturnsForbiddenWhenRegistrationDisabled(): void
    {
        $this->setInMemoryConfig('security.enableWebAuthnSupport', 'true');
        $this->setInMemoryConfig('security.enableRegistration', 'false');

        $requestData = json_encode(['username' => 'nonexistent_user_' . uniqid()]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $response = $controller->prepare($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
}
