<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

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
    public function testListRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->list($request);
    }

    /**
     * @throws \Exception
     */
    public function testCsvExportRequiresAuthentication(): void
    {
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->csvExport();
    }

    /**
     * @throws \Exception
     */
    public function testUserDataRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->userData($request);
    }

    /**
     * @throws \Exception
     */
    public function testUserPermissionsRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->userPermissions($request);
    }

    /**
     * @throws \Exception
     */
    public function testActivateRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrfToken' => 'test-token', 'userId' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->activate($request);
    }

    /**
     * @throws \Exception
     */
    public function testOverwritePasswordRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrf' => 'test-token',
            'userId' => 1,
            'newPassword' => 'password123',
            'passwordRepeat' => 'password123',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->overwritePassword($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteUserRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrfToken' => 'test-token', 'userId' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->deleteUser($request);
    }

    /**
     * @throws \Exception
     */
    public function testAddUserRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrf' => 'test-token',
            'userName' => 'testuser',
            'realName' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'passwordConfirm' => 'password123',
            'automaticPassword' => false,
            'isSuperAdmin' => false,
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->addUser($request);
    }

    /**
     * @throws \Exception
     */
    public function testEditUserRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrfToken' => 'test-token',
            'userId' => 1,
            'display_name' => 'Test User',
            'email' => 'test@example.com',
            'user_status' => 'active',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->editUser($request);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateUserRightsRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrfToken' => 'test-token',
            'userId' => 1,
            'userRights' => [1, 2, 3],
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->updateUserRights($request);
    }

    /**
     * @throws \Exception
     */
    public function testActivateWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->activate($request);
    }

    /**
     * @throws \Exception
     */
    public function testOverwritePasswordWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->overwritePassword($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteUserWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->deleteUser($request);
    }

    /**
     * @throws \Exception
     */
    public function testAddUserWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->addUser($request);
    }

    /**
     * @throws \Exception
     */
    public function testActivateWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode(['userId' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->activate($request);
    }

    /**
     * @throws \Exception
     */
    public function testOverwritePasswordWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode([
            'userId' => 1,
            'newPassword' => 'password123',
            'passwordRepeat' => 'password123',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->overwritePassword($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteUserWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode(['userId' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->deleteUser($request);
    }

    /**
     * @throws \Exception
     */
    public function testAddUserWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode([
            'userName' => 'testuser',
            'realName' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new UserController();

        $this->expectException(\Exception::class);
        $controller->addUser($request);
    }
}
