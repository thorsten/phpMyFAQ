<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(CategoryController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractApiController::class)]
#[UsesClass(PaginatedResponseOptions::class)]
final class CategoryControllerWebTest extends ControllerWebTestCase
{
    public function testCategoriesEndpointReturnsJson(): void
    {
        $response = $this->requestApi('GET', '/v3.2/categories');

        self::assertResponseIsSuccessful($response);
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }
}
