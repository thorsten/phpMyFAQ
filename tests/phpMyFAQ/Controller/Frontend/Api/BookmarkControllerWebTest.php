<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(BookmarkController::class)]
#[UsesNamespace('phpMyFAQ')]
final class BookmarkControllerWebTest extends ControllerWebTestCase
{
    public function testBookmarkCreateReturnsUnauthorizedWhenAnonymous(): void
    {
        $response = $this->requestApi('POST', '/bookmark/create');

        self::assertResponseStatusCodeSame(401, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }
}
