<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Session\Token;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(BookmarkController::class)]
#[UsesNamespace('phpMyFAQ')]
final class BookmarkControllerAuthenticatedTest extends ApiControllerTestCase
{
    public function testCreateThrowsExceptionForInvalidJson(): void
    {
        $controller = new BookmarkController();
        $session = $this->createSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $this->expectException(\JsonException::class);

        $controller->create(new Request([], [], [], [], [], [], ''));
    }

    public function testDeleteThrowsExceptionForInvalidJson(): void
    {
        $controller = new BookmarkController();
        $session = $this->createSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $this->expectException(\JsonException::class);

        $controller->delete(new Request([], [], [], [], [], [], ''));
    }

    public function testDeleteAllThrowsExceptionForInvalidJson(): void
    {
        $controller = new BookmarkController();
        $session = $this->createSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $this->expectException(\JsonException::class);

        $controller->deleteAll(new Request([], [], [], [], [], [], ''));
    }

    public function testCreateReturnsSuccessForValidBookmarkRequest(): void
    {
        $controller = new BookmarkController();
        $session = $this->createSession();
        $csrfToken = Token::getInstance($session)->getTokenString('add-bookmark');
        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5('add-bookmark'), 0, 10))] = $csrfToken;
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $response = $controller->create(
            new Request([], [], [], [], [], [], '{"id":1,"csrfToken":"' . $csrfToken . '"}'),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
        self::assertArrayHasKey('csrfToken', $payload);
    }

    public function testCreateReturnsUnauthorizedForInvalidCsrfToken(): void
    {
        $controller = new BookmarkController();
        $session = $this->createSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $response = $controller->create(new Request([], [], [], [], [], [], '{"id":1,"csrfToken":"invalid"}'));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertStringContainsString('error', (string) $response->getContent());
    }

    public function testCreateReturnsBadRequestForInvalidBookmarkId(): void
    {
        $controller = new BookmarkController();
        $session = $this->createSession();
        $csrfToken = Token::getInstance($session)->getTokenString('add-bookmark');
        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5('add-bookmark'), 0, 10))] = $csrfToken;
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $response = $controller->create(
            new Request([], [], [], [], [], [], '{"id":"not-a-number","csrfToken":"' . $csrfToken . '"}'),
        );

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertStringContainsString('error', (string) $response->getContent());
    }

    public function testDeleteReturnsSuccessForExistingBookmark(): void
    {
        $controller = new BookmarkController();
        $session = $this->createSession();
        $addCsrfToken = Token::getInstance($session)->getTokenString('add-bookmark');
        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5('add-bookmark'), 0, 10))] = $addCsrfToken;
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $createResponse = $controller->create(
            new Request([], [], [], [], [], [], '{"id":1,"csrfToken":"' . $addCsrfToken . '"}'),
        );
        self::assertSame(Response::HTTP_OK, $createResponse->getStatusCode());

        $deleteCsrfToken = Token::getInstance($session)->getTokenString('delete-bookmark');
        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5('delete-bookmark'), 0, 10))] = $deleteCsrfToken;

        $response = $controller->delete(
            new Request([], [], [], [], [], [], '{"id":1,"csrfToken":"' . $deleteCsrfToken . '"}'),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
        self::assertArrayHasKey('csrfToken', $payload);
    }

    public function testDeleteAllReturnsUnauthorizedForInvalidCsrfToken(): void
    {
        $controller = new BookmarkController();
        $session = $this->createSession();
        Token::getInstance($session)->getTokenString('delete-all-bookmarks');
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $response = $controller->deleteAll(new Request([], [], [], [], [], [], '{"csrfToken":"invalid"}'));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertStringContainsString('error', (string) $response->getContent());
    }

    public function testDeleteAllReturnsSuccessWhenUserHasNoBookmarks(): void
    {
        $controller = new BookmarkController();
        $session = $this->createSession();
        $deleteAllCsrfToken = Token::getInstance($session)->getTokenString('delete-all-bookmarks');
        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5('delete-all-bookmarks'), 0, 10))] =
            $deleteAllCsrfToken;
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $response = $controller->deleteAll(
            new Request([], [], [], [], [], [], '{"csrfToken":"' . $deleteAllCsrfToken . '"}'),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
    }

    public function testDeleteReturnsBadRequestForInvalidBookmarkId(): void
    {
        $controller = new BookmarkController();
        $session = $this->createSession();
        $deleteCsrfToken = Token::getInstance($session)->getTokenString('delete-bookmark');
        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5('delete-bookmark'), 0, 10))] = $deleteCsrfToken;
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $response = $controller->delete(
            new Request([], [], [], [], [], [], '{"id":"not-a-number","csrfToken":"' . $deleteCsrfToken . '"}'),
        );

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertStringContainsString('error', (string) $response->getContent());
    }

    public function testDeleteAllReturnsSuccessWhenBookmarksExist(): void
    {
        $controller = new BookmarkController();
        $session = $this->createSession();
        $addCsrfToken = Token::getInstance($session)->getTokenString('add-bookmark');
        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5('add-bookmark'), 0, 10))] = $addCsrfToken;
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $controller->create(new Request([], [], [], [], [], [], '{"id":1,"csrfToken":"' . $addCsrfToken . '"}'));

        $deleteAllCsrfToken = Token::getInstance($session)->getTokenString('delete-all-bookmarks');
        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5('delete-all-bookmarks'), 0, 10))] =
            $deleteAllCsrfToken;

        $response = $controller->deleteAll(
            new Request([], [], [], [], [], [], '{"csrfToken":"' . $deleteAllCsrfToken . '"}'),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
    }
}
