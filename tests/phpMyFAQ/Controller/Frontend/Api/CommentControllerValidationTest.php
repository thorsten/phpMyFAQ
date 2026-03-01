<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Comments;
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
        ?Comments $comments = null,
        ?StopWords $stopWords = null,
        ?User $user = null,
    ): CommentController
    {
        $language = $this->createStub(Language::class);
        $language->method('setLanguageFromConfiguration')->willReturn('en');
        $language->method('setLanguageWithDetection')->willReturn('en');

        return new CommentController(
            $this->createStub(Faq::class),
            $comments ?? $this->createStub(Comments::class),
            $stopWords ?? $this->createStub(StopWords::class),
            $this->createStub(UserSession::class),
            $language,
            $user ?? $this->createStub(User::class),
            $this->createStub(Notification::class),
            $this->createStub(News::class),
            $this->createStub(Gravatar::class),
        );
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
        $session = $this->createSession();
        $csrfToken = Token::getInstance($session)->getTokenString('add-comment');
        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5('add-comment'), 0, 10))] = $csrfToken;
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
        $session = $this->createSession();
        $csrfToken = Token::getInstance($session)->getTokenString('add-comment');
        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5('add-comment'), 0, 10))] = $csrfToken;
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
        $session = $this->createSession();
        $csrfToken = Token::getInstance($session)->getTokenString('add-comment');
        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5('add-comment'), 0, 10))] = $csrfToken;

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
}
