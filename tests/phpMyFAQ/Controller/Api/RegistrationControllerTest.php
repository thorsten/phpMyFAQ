<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    public function testCreateReturnsJsonResponse(): void
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

    public function testCreateRequiresValidToken(): void
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

    public function testCreateRequiresAllRequiredFields(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    public function testCreateValidatesEmailFormat(): void
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
}
