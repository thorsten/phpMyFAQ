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
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
class BookmarkControllerTest extends TestCase
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
    public function testCreateRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'id' => 1,
            'csrfToken' => 'test-token',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new BookmarkController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \JsonException
     */
    public function testCreateWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new BookmarkController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \JsonException
     */
    public function testDeleteRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'id' => 1,
            'csrfToken' => 'test-token',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new BookmarkController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \JsonException
     */
    public function testDeleteWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new BookmarkController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \JsonException
     */
    public function testDeleteAllRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrfToken' => 'test-token',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new BookmarkController();

        $this->expectException(\Exception::class);
        $controller->deleteAll($request);
    }

    /**
     * @throws \JsonException
     */
    public function testDeleteAllWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new BookmarkController();

        $this->expectException(\Exception::class);
        $controller->deleteAll($request);
    }

    /**
     * @throws \JsonException
     */
    public function testCreateWithMissingIdThrowsException(): void
    {
        $requestData = json_encode([
            'csrfToken' => 'test-token',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new BookmarkController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \JsonException
     */
    public function testDeleteWithMissingIdThrowsException(): void
    {
        $requestData = json_encode([
            'csrfToken' => 'test-token',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new BookmarkController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \JsonException
     */
    public function testCreateWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode([
            'id' => 1,
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new BookmarkController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \JsonException
     */
    public function testDeleteWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode([
            'id' => 1,
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new BookmarkController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }
}
