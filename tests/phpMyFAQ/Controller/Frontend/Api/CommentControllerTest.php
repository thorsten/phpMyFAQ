<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Comments;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Faq;
use phpMyFAQ\Language;
use phpMyFAQ\News;
use phpMyFAQ\Notification;
use phpMyFAQ\Service\Gravatar;
use phpMyFAQ\StopWords;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\UserSession;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(CommentController::class)]
#[UsesNamespace('phpMyFAQ')]
class CommentControllerTest extends TestCase
{
    private Configuration $configuration;
    private Faq $faq;
    private Comments $comments;
    private StopWords $stopWords;
    private UserSession $userSession;
    private Language $language;
    private User $user;
    private Notification $notification;
    private News $news;
    private Gravatar $gravatar;

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

        $this->faq = $this->createStub(Faq::class);
        $this->comments = $this->createStub(Comments::class);
        $this->stopWords = $this->createStub(StopWords::class);
        $this->userSession = $this->createStub(UserSession::class);
        $this->language = $this->createStub(Language::class);
        $this->user = $this->createStub(User::class);
        $this->notification = $this->createStub(Notification::class);
        $this->news = $this->createStub(News::class);
        $this->gravatar = $this->createStub(Gravatar::class);
    }

    private function createController(): CommentController
    {
        return new CommentController(
            $this->faq,
            $this->comments,
            $this->stopWords,
            $this->userSession,
            $this->language,
            $this->user,
            $this->notification,
            $this->news,
            $this->gravatar,
        );
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function testCreateWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = $this->createController();

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
        $controller = $this->createController();

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
        $controller = $this->createController();

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
        $controller = $this->createController();

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
        $controller = $this->createController();

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
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }
}
