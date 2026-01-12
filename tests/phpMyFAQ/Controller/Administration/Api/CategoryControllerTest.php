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
class CategoryControllerTest extends TestCase
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
    public function testDeleteRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrfToken' => 'test-token', 'categoryId' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new CategoryController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testPermissionsRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new CategoryController();

        $this->expectException(\Exception::class);
        $controller->permissions($request);
    }

    /**
     * @throws \Exception
     */
    public function testTranslationsRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new CategoryController();

        $this->expectException(\Exception::class);
        $controller->translations($request);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateOrderRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrfToken' => 'test-token', 'categoryId' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new CategoryController();

        $this->expectException(\Exception::class);
        $controller->updateOrder($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new CategoryController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateOrderWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new CategoryController();

        $this->expectException(\Exception::class);
        $controller->updateOrder($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode(['categoryId' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new CategoryController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateOrderWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode(['categoryId' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new CategoryController();

        $this->expectException(\Exception::class);
        $controller->updateOrder($request);
    }
}
