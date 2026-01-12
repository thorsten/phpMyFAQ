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

        $this->configuration = Configuration::getConfigurationInstance();
    }

    /**
     * @throws \JsonException
     */
    public function testCreateWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \JsonException
     */
    public function testCreateWithMissingRealnameThrowsException(): void
    {
        $requestData = json_encode([
            'name' => 'testuser',
            'email' => 'test@example.com',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \JsonException
     */
    public function testCreateWithMissingUsernameThrowsException(): void
    {
        $requestData = json_encode([
            'realname' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \JsonException
     */
    public function testCreateWithInvalidEmailThrowsException(): void
    {
        $requestData = json_encode([
            'realname' => 'Test User',
            'name' => 'testuser',
            'email' => 'invalid-email',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \JsonException
     */
    public function testCreateWithEmptyEmailThrowsException(): void
    {
        $requestData = json_encode([
            'realname' => 'Test User',
            'name' => 'testuser',
            'email' => '',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \JsonException
     */
    public function testCreateWithIsVisibleParameterThrowsException(): void
    {
        $requestData = json_encode([
            'realname' => 'Test User',
            'name' => 'testuser',
            'email' => 'test@example.com',
            'isVisible' => true,
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }
}
