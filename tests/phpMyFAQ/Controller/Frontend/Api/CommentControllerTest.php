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
class CommentControllerTest extends TestCase
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
     * @throws Exception
     * @throws \JsonException
     */
    public function testCreateWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new CommentController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testCreateWithMissingCsrfTokenReturnsUnauthorized(): void
    {
        $requestData = json_encode([
            'type' => 'faq',
            'id' => 1,
            'user' => 'Test User',
            'mail' => 'test@example.com',
            'comment_text' => 'Test comment',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new CommentController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testCreateWithEmptyCommentTextReturnsBadRequest(): void
    {
        $requestData = json_encode([
            'type' => 'faq',
            'id' => 1,
            'user' => 'Test User',
            'mail' => 'test@example.com',
            'comment_text' => '',
            'pmf-csrf-token' => 'test-token',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new CommentController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testCreateWithMissingUserThrowsException(): void
    {
        $requestData = json_encode([
            'type' => 'faq',
            'id' => 1,
            'mail' => 'test@example.com',
            'comment_text' => 'Test comment',
            'pmf-csrf-token' => 'test-token',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new CommentController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testCreateWithInvalidEmailThrowsException(): void
    {
        $requestData = json_encode([
            'type' => 'faq',
            'id' => 1,
            'user' => 'Test User',
            'mail' => 'invalid-email',
            'comment_text' => 'Test comment',
            'pmf-csrf-token' => 'test-token',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new CommentController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testCreateWithNewsTypeThrowsException(): void
    {
        $requestData = json_encode([
            'type' => 'news',
            'newsId' => 1,
            'user' => 'Test User',
            'mail' => 'test@example.com',
            'comment_text' => 'Test comment',
            'pmf-csrf-token' => 'test-token',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new CommentController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }
}
