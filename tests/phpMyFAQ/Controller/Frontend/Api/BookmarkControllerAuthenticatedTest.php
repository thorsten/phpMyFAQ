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
    public function testCreateReturnsUnauthorizedForInvalidCsrfToken(): void
    {
        $controller = new BookmarkController();
        $session = $this->createSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $response = $controller->create(new Request([], [], [], [], [], [], '{"id":1,"csrfToken":"invalid"}'));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertStringContainsString('error', (string) $response->getContent());
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
}
