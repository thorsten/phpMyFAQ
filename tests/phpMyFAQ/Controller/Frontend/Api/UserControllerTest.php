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
class UserControllerTest extends TestCase
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
     * @throws \Exception
     */
    public function testUpdateDataRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'userid' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_visible' => 'on',
            'pmf-csrf-token' => 'test-token',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->updateData($request);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateDataWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->updateData($request);
    }

    /**
     * @throws \Exception
     */
    public function testExportUserDataRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->exportUserData($request);
    }

    /**
     * @throws \Exception
     */
    public function testRequestUserRemovalRequiresValidToken(): void
    {
        $requestData = json_encode([
            'userId' => 1,
            'name' => 'Test User',
            'loginname' => 'testuser',
            'email' => 'test@example.com',
            'question' => 'Please remove my account',
            'pmf-csrf-token' => 'test-token',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->requestUserRemoval($request);
    }

    /**
     * @throws \Exception
     */
    public function testRequestUserRemovalWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->requestUserRemoval($request);
    }

    /**
     * @throws \Exception
     */
    public function testRemoveTwofactorConfigRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrfToken' => 'test-token',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->removeTwofactorConfig($request);
    }

    /**
     * @throws \Exception
     */
    public function testRemoveTwofactorConfigWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->removeTwofactorConfig($request);
    }
}
