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
class NewsControllerTest extends TestCase
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
    public function testCreateRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrfToken' => 'test-token',
            'newsHeader' => 'Test',
            'news' => 'Content',
            'authorName' => 'Author',
            'authorEmail' => 'test@example.com',
            'active' => 'y',
            'comment' => 'y',
            'langTo' => 'en',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new NewsController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrfToken' => 'test-token', 'id' => 1]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new NewsController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrfToken' => 'test-token',
            'id' => 1,
            'newsHeader' => 'Test',
            'news' => 'Content',
            'authorName' => 'Author',
            'authorEmail' => 'test@example.com',
            'active' => 'y',
            'comment' => 'y',
            'langTo' => 'en',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new NewsController();

        $this->expectException(\Exception::class);
        $controller->update($request);
    }

    /**
     * @throws \Exception
     */
    public function testActivateRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrfToken' => 'test-token', 'id' => 1, 'status' => 'y']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new NewsController();

        $this->expectException(\Exception::class);
        $controller->activate($request);
    }

    /**
     * @throws \Exception
     */
    public function testCreateWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new NewsController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new NewsController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testCreateWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode([
            'newsHeader' => 'Test',
            'news' => 'Content',
            'authorName' => 'Author',
            'authorEmail' => 'test@example.com',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new NewsController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }
}
