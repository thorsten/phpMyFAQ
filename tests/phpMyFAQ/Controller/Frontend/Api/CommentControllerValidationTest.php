<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Comments;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Faq;
use phpMyFAQ\Language;
use phpMyFAQ\News;
use phpMyFAQ\Notification;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Service\Gravatar;
use phpMyFAQ\Session\Token;
use phpMyFAQ\StopWords;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\UserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(CommentController::class)]
#[UsesNamespace('phpMyFAQ')]
final class CommentControllerValidationTest extends ApiControllerTestCase
{
    private function createController(
        ?Faq $faq = null,
        ?Comments $comments = null,
        ?StopWords $stopWords = null,
        ?User $user = null,
        ?UserSession $userSession = null,
        ?Notification $notification = null,
        ?Gravatar $gravatar = null,
    ): CommentController {
        $language = $this->createStub(Language::class);
        $language->method('setLanguageFromConfiguration')->willReturn('en');
        $language->method('setLanguageWithDetection')->willReturn('en');

        return new CommentController(
            $faq ?? $this->createStub(Faq::class),
            $comments ?? $this->createStub(Comments::class),
            $stopWords ?? $this->createStub(StopWords::class),
            $userSession ?? $this->createStub(UserSession::class),
            $language,
            $user ?? $this->createStub(User::class),
            $notification ?? $this->createStub(Notification::class),
            $this->createStub(News::class),
            $gravatar ?? $this->createStub(Gravatar::class),
        );
    }

    private function createValidCsrfSession(): array
    {
        $session = $this->createSession();
        $csrfToken = Token::getInstance($session)->getTokenString('add-comment');
        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5('add-comment'), 0, 10))] = $csrfToken;

        return [$session, $csrfToken];
    }

    public function testCreateThrowsExceptionWhenCsrfTokenIsMissing(): void
    {
        $controller = $this->createController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing CSRF token');

        $controller->create(Request::create('/api/comment/create', 'POST', content: json_encode([
            'type' => 'faq',
            'id' => 1,
            'user' => 'Test User',
            'mail' => 'test@example.com',
            'comment_text' => 'Test comment',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenCsrfTokenIsInvalid(): void
    {
        $controller = $this->createController();
        [$session] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid CSRF token');

        $controller->create(Request::create('/api/comment/create', 'POST', content: json_encode([
            'pmf-csrf-token' => 'invalid-token',
            'type' => 'faq',
            'id' => 1,
            'user' => 'Test User',
            'mail' => 'test@example.com',
            'comment_text' => 'Test comment',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenUserIsMissing(): void
    {
        $controller = $this->createController();
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing user');

        $controller->create(Request::create('/api/comment/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'type' => 'faq',
            'id' => 1,
            'mail' => 'test@example.com',
            'comment_text' => 'Test comment',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenEmailIsMissing(): void
    {
        $controller = $this->createController();
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing email');

        $controller->create(Request::create('/api/comment/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'type' => 'faq',
            'id' => 1,
            'user' => 'Test User',
            'comment_text' => 'Test comment',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenCommentTextIsMissing(): void
    {
        $controller = $this->createController();
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing or empty comment text');

        $controller->create(Request::create('/api/comment/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'type' => 'faq',
            'id' => 1,
            'user' => 'Test User',
            'mail' => 'test@example.com',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenNewsTypeIsRequested(): void
    {
        $controller = $this->createController();
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('News comments not supported');

        $controller->create(Request::create('/api/comment/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'type' => 'news',
            'newsId' => 1,
            'user' => 'Test User',
            'mail' => 'test@example.com',
            'comment_text' => 'Test comment',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenEmailIsInvalid(): void
    {
        $controller = $this->createController();
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid email address');

        $controller->create(Request::create('/api/comment/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'type' => 'faq',
            'id' => 1,
            'user' => 'Test User',
            'mail' => 'not-an-email',
            'comment_text' => 'Test comment',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateReturnsForbiddenWhenCommentsAreNotAllowed(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['records.allowCommentsForGuests' => '0']);

        $controller = $this->createController();
        $currentUser = $this->createAuthenticatedUserMock(-1);
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => false]);
        $this->injectControllerState($controller, $currentUser, $this->createSession());

        $response = $controller->create(Request::create('/api/comment/create', 'POST', content: json_encode([
            'pmf-csrf-token' => 'token',
            'type' => 'faq',
            'id' => 1,
            'user' => 'Test User',
            'mail' => 'test@example.com',
            'comment_text' => 'Test comment',
        ], JSON_THROW_ON_ERROR)));

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }

    public function testCreateReturnsBadRequestWhenCaptchaValidationFails(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['records.allowCommentsForGuests' => '1']);

        $controller = $this->createController();
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $session);

        $request = Request::create('/api/comment/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'type' => 'faq',
            'id' => 1,
            'user' => 'Test User',
            'mail' => 'test@example.com',
            'comment_text' => 'Test comment',
            'captcha' => 'invalid',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }

    public function testCreateReturnsBadRequestWhenCommentTargetIdIsInvalid(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['records.allowCommentsForGuests' => '1']);

        $controller = $this->createController();
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $session);

        $request = Request::create('/api/comment/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'type' => 'faq',
            'id' => 0,
            'user' => 'Test User',
            'mail' => 'test@example.com',
            'comment_text' => 'Test comment',
            'captcha' => 'ignored-for-logged-in-user',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }

    public function testCreateReturnsBadRequestWhenCommentTypeIsUnsupported(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['records.allowCommentsForGuests' => '1']);

        $controller = $this->createController();
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $session);

        $request = Request::create('/api/comment/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'type' => 'unsupported',
            'id' => 1,
            'user' => 'Test User',
            'mail' => 'test@example.com',
            'comment_text' => 'Test comment',
            'captcha' => 'ignored-for-logged-in-user',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }

    public function testCreateReturnsBadRequestWhenStopWordValidationFails(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'records.allowCommentsForGuests' => '1',
            'spam.enableCaptchaCode' => '0',
        ]);

        $stopWords = $this->createStub(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(false);

        $controller = $this->createController(stopWords: $stopWords);
        [$session, $csrfToken] = $this->createValidCsrfSession();

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(false);
        $currentUser->method('getUserId')->willReturn(-1);
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $session);

        $request = Request::create('/api/comment/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'type' => 'faq',
            'id' => 1,
            'user' => 'Test User',
            'mail' => 'test@example.com',
            'comment_text' => 'Test comment',
            'captcha' => 'ignored',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }

    public function testCreateReturnsBadRequestWhenCommentTextIsEmpty(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['records.allowCommentsForGuests' => '1']);

        $controller = $this->createController();
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $session);

        $request = Request::create('/api/comment/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'type' => 'faq',
            'id' => 1,
            'user' => 'Test User',
            'mail' => 'test@example.com',
            'comment_text' => '',
            'captcha' => 'ignored-for-logged-in-user',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Please add your name, your e-mail address and a comment!', $payload['error']);
    }

    public function testCreateReturnsBadRequestWhenFaqIsInactive(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'records.allowCommentsForGuests' => '1',
            'main.enableCommentEditor' => '0',
        ]);

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('isActive')->with(1, 'en', 'faq')->willReturn(false);

        $comments = $this->createMock(Comments::class);
        $comments->expects($this->once())->method('isCommentAllowed')->with(1, 'en', 'faq')->willReturn(true);
        $comments->expects($this->never())->method('create');

        $stopWords = $this->createStub(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(true);

        $controller = $this->createController(faq: $faq, comments: $comments, stopWords: $stopWords);
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $session);

        $request = Request::create('/api/comment/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'type' => 'faq',
            'id' => 1,
            'user' => 'Test User',
            'mail' => 'test@example.com',
            'comment_text' => 'Test comment',
            'captcha' => 'ignored',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Please add your name, your e-mail address and a comment!', $payload['error']);
    }

    public function testCreateReturnsBadRequestWhenFaqCommentsAreNotAllowedForTarget(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'records.allowCommentsForGuests' => '1',
            'main.enableCommentEditor' => '0',
        ]);

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->never())->method('isActive');

        $comments = $this->createMock(Comments::class);
        $comments->expects($this->once())->method('isCommentAllowed')->with(1, 'en', 'faq')->willReturn(false);
        $comments->expects($this->never())->method('create');

        $stopWords = $this->createStub(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(true);

        $controller = $this->createController(faq: $faq, comments: $comments, stopWords: $stopWords);
        [$session, $csrfToken] = $this->createValidCsrfSession();

        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $session);

        $request = Request::create('/api/comment/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'type' => 'faq',
            'id' => 1,
            'user' => 'Test User',
            'mail' => 'test@example.com',
            'comment_text' => 'Test comment',
            'captcha' => 'ignored',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }

    public function testCreateReturnsSuccessWhenCommentIsStored(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'records.allowCommentsForGuests' => '1',
            'main.enableCommentEditor' => '1',
        ]);

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('isActive')->with(1, 'en', 'faq')->willReturn(true);
        $faq->expects($this->once())->method('getFaq')->with(1);

        $comments = $this->createMock(Comments::class);
        $comments->expects($this->once())->method('isCommentAllowed')->with(1, 'en', 'faq')->willReturn(true);
        $comments
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function ($comment): bool {
                return $comment->getRecordId() === 1
                && $comment->getType() === 'faq'
                && $comment->getUsername() === 'Test User'
                && $comment->getEmail() === 'test@example.com'
                && str_contains($comment->getComment(), '<strong>Hello</strong>');
            }))
            ->willReturn(true);

        $stopWords = $this->createStub(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(true);

        $userSession = $this->createMock(UserSession::class);
        $userSession->expects($this->once())->method('setCurrentUser')->willReturnSelf();
        $userSession->expects($this->once())->method('userTracking')->with('save_comment', 1);

        $notification = $this->createMock(Notification::class);
        $notification->expects($this->once())->method('sendFaqCommentNotification');

        $gravatar = $this->createMock(Gravatar::class);
        $gravatar
            ->expects($this->once())
            ->method('getImageUrl')
            ->with('test@example.com', ['size' => 50, 'default' => 'mm'])
            ->willReturn('https://secure.gravatar.com/avatar/test');

        $controller = $this->createController(
            faq: $faq,
            comments: $comments,
            stopWords: $stopWords,
            userSession: $userSession,
            notification: $notification,
            gravatar: $gravatar,
        );
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $session);

        $request = Request::create('/api/comment/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'type' => 'faq',
            'id' => 1,
            'user' => 'Test User',
            'mail' => 'test@example.com',
            'comment_text' => '<strong>Hello</strong> world',
            'captcha' => 'ignored-for-logged-in-user',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('Test User', $payload['commentData']['username']);
        self::assertSame('https://secure.gravatar.com/avatar/test', $payload['commentData']['gravatarUrl']);
        self::assertArrayHasKey('success', $payload);
    }

    public function testCreateReturnsBadRequestWhenCommentCreateFailsAfterValidation(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'records.allowCommentsForGuests' => '1',
            'main.enableCommentEditor' => '0',
        ]);

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('isActive')->with(1, 'en', 'faq')->willReturn(true);
        $faq->expects($this->never())->method('getFaq');

        $comments = $this->createMock(Comments::class);
        $comments->expects($this->once())->method('isCommentAllowed')->with(1, 'en', 'faq')->willReturn(true);
        $comments->expects($this->once())->method('create')->willReturn(false);

        $stopWords = $this->createStub(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(true);

        $userSession = $this->createMock(UserSession::class);
        $userSession->expects($this->once())->method('setCurrentUser')->willReturnSelf();
        $userSession->expects($this->exactly(2))->method('userTracking')->withAnyParameters();

        $controller = $this->createController(
            faq: $faq,
            comments: $comments,
            stopWords: $stopWords,
            userSession: $userSession,
        );
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $session);

        $request = Request::create('/api/comment/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'type' => 'faq',
            'id' => 1,
            'user' => 'Test User',
            'mail' => 'test@example.com',
            'comment_text' => 'Hello world',
            'captcha' => 'ignored-for-logged-in-user',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }

    public function testCreateStoresSanitizedHtmlCommentForLoggedInUser(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'records.allowCommentsForGuests' => '1',
            'main.enableCommentEditor' => '1',
        ]);

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('isActive')->with(1, 'en', 'faq')->willReturn(true);
        $faq->expects($this->once())->method('getFaq')->with(1);

        $comments = $this->createMock(Comments::class);
        $comments->expects($this->once())->method('isCommentAllowed')->with(1, 'en', 'faq')->willReturn(true);
        $comments
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function ($comment): bool {
                return $comment->getComment() === '<a title="safe">Click</a><strong>OK</strong>';
            }))
            ->willReturn(true);

        $stopWords = $this->createStub(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(true);

        $userSession = $this->createMock(UserSession::class);
        $userSession->expects($this->once())->method('setCurrentUser')->willReturnSelf();
        $userSession->expects($this->once())->method('userTracking')->with('save_comment', 1);

        $notification = $this->createMock(Notification::class);
        $notification->expects($this->once())->method('sendFaqCommentNotification');

        $gravatar = $this->createMock(Gravatar::class);
        $gravatar
            ->expects($this->once())
            ->method('getImageUrl')
            ->with('test@example.com', ['size' => 50, 'default' => 'mm'])
            ->willReturn('https://secure.gravatar.com/avatar/guest');

        $controller = $this->createController(
            faq: $faq,
            comments: $comments,
            stopWords: $stopWords,
            userSession: $userSession,
            notification: $notification,
            gravatar: $gravatar,
        );
        [$session, $csrfToken] = $this->createValidCsrfSession();

        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $session);

        $request = Request::create('/api/comment/create', 'POST', content: json_encode([
            'pmf-csrf-token' => $csrfToken,
            'type' => 'faq',
            'id' => 1,
            'user' => 'Test User',
            'mail' => 'test@example.com',
            'comment_text' => '<a href="javascript:alert(1)" title="safe" onclick="evil()">Click</a><strong>OK</strong>',
            'captcha' => 'ignored-for-logged-in-user',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<a title="safe">Click</a><strong>OK</strong>', $payload['commentData']['comment']);
        self::assertArrayHasKey('success', $payload);
    }
}
