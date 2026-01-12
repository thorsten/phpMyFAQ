<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class WebAuthnControllerTest extends TestCase
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

        $this->configuration = Configuration::getConfigurationInstance();
    }

    /**
     * @throws \JsonException
     * @throws \Exception
     */
    public function testPrepareWithInvalidJsonThrowsException(): void
    {
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
        $requestData = json_encode([
            'username' => 'testuser',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new WebAuthnController();

        $this->expectException(\Exception::class);
        $controller->login($request);
    }
}
