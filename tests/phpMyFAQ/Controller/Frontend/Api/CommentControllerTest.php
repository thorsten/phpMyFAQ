<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Comments;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\Comment;
use phpMyFAQ\Faq;
use phpMyFAQ\Language;
use phpMyFAQ\News;
use phpMyFAQ\Notification;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Service\Gravatar;
use phpMyFAQ\Session\Token;
use phpMyFAQ\StopWords;
use phpMyFAQ\Strings;
use phpMyFAQ\TestDatabaseTrait;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\UserSession;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(CommentController::class)]
#[UsesNamespace('phpMyFAQ')]
class CommentControllerTest extends TestCase
{
    use TestDatabaseTrait;

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

        $this->configuration = $this->createTestConfiguration();

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

    protected function tearDown(): void
    {
        Token::resetInstanceForTests();
        $_COOKIE = [];

        parent::tearDown();
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

    /**
     * @throws \Exception
     */
    public function testCreateDetectsTheLanguageWhenDetectionIsEnabled(): void
    {
        self::assertTrue($this->configuration->set('main.languageDetection', 'true'));
        self::assertTrue($this->configuration->set('records.allowCommentsForGuests', 'false'));

        $language = $this->createMock(Language::class);
        $language->expects($this->once())->method('setLanguageWithDetection')->willReturn('en');
        $language->expects($this->never())->method('setLanguageFromConfiguration');
        $this->language = $language;

        $controller = $this->createController();
        $this->injectControllerState($controller, $this->createGuestUser(), $this->createSession());

        $response = $controller->create(new Request([], [], [], [], [], [], '{}'));

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testCreateRejectsABodyThatIsNotAJsonObject(): void
    {
        self::assertTrue($this->configuration->set('records.allowCommentsForGuests', 'true'));

        $controller = $this->createController();
        $this->injectControllerState($controller, $this->createGuestUser(), $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The request body must be a JSON object');

        $controller->create(new Request([], [], [], [], [], [], '[1, 2, 3]'));
    }

    /**
     * @throws \Exception
     */
    public function testCreateRejectsGuestsWithAnInvalidCaptcha(): void
    {
        self::assertTrue($this->configuration->set('records.allowCommentsForGuests', 'true'));
        self::assertTrue($this->configuration->set('spam.enableCaptchaCode', 'true'));

        $controller = $this->createController();
        $session = $this->createSession();
        $this->injectControllerState($controller, $this->createGuestUser(), $session);

        $response = $controller->create($this->createCommentRequest($session, [
            'comment_text' => 'A guest comment',
            'captcha' => 'wrong',
        ]));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            ['error' => Translation::get('msgCaptcha')],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @throws \Exception
     */
    public function testCreateKeepsSanitizedHtmlForLoggedInUsersWhenTheEditorIsEnabled(): void
    {
        self::assertTrue($this->configuration->set('main.enableCommentEditor', 'true'));

        $this->faq = $this->createStub(Faq::class);
        $this->faq->method('isFaqAccessibleForUser')->willReturn(true);
        $this->faq->method('isActive')->willReturn(true);
        $this->stopWords = $this->createStub(StopWords::class);
        $this->stopWords->method('checkBannedWord')->willReturn(true);

        $storedComment = null;
        $comments = $this->createMock(Comments::class);
        $comments->method('isCommentAllowed')->willReturn(true);
        $comments
            ->expects($this->once())
            ->method('create')
            ->willReturnCallback(static function (Comment $comment) use (&$storedComment): bool {
                $storedComment = $comment;
                return true;
            });
        $this->comments = $comments;

        $controller = $this->createController();
        $session = $this->createSession();
        $this->injectControllerState($controller, $this->createLoggedInUser(), $session);

        $response = $controller->create($this->createCommentRequest($session, [
            'comment_text' => '<b onclick="alert(1)">bold</b> text',
        ]));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertInstanceOf(Comment::class, $storedComment);
        self::assertStringContainsString('<b>bold</b>', $storedComment->getComment());
        self::assertStringNotContainsString('onclick', $storedComment->getComment());
    }

    private function createSession(): Session
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        return $session;
    }

    private function createGuestUser(): CurrentUser
    {
        $permission = $this->createStub(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn(false);

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(false);
        $currentUser->method('getUserId')->willReturn(-1);

        return $currentUser;
    }

    private function createLoggedInUser(): CurrentUser
    {
        $permission = $this->createStub(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn(true);

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(1);

        return $currentUser;
    }

    private function injectControllerState(CommentController $controller, CurrentUser $currentUser, Session $session): void
    {
        new \ReflectionProperty($controller, 'currentUser')->setValue($controller, $currentUser);
        new \ReflectionProperty($controller, 'session')->setValue($controller, $session);
        new \ReflectionProperty($controller, 'configuration')->setValue($controller, $this->configuration);
    }

    /**
     * @param array<string, mixed> $overrides
     * @throws \Exception
     */
    private function createCommentRequest(Session $session, array $overrides): Request
    {
        $token = Token::getInstance($session)->getTokenString('add-comment');
        $_COOKIE['pmf-csrf-token-' . substr(md5('add-comment'), 0, 10)] = $token;

        $payload = [
            'type' => 'faq',
            'id' => 1,
            'user' => 'Commenter',
            'mail' => 'commenter@example.com',
            'comment_text' => 'A comment',
            'pmf-csrf-token' => $token,
            ...$overrides,
        ];

        return new Request([], [], [], [], [], [], json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
