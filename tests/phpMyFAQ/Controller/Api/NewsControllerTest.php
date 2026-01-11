<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

#[AllowMockObjectsWithoutExpectations]
class NewsControllerTest extends TestCase
{
    public function testListReturnsJsonResponse(): void
    {
        $controller = new NewsController();
        $response = $controller->list();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testListReturnsValidStatusCode(): void
    {
        $controller = new NewsController();
        $response = $controller->list();

        $this->assertContains($response->getStatusCode(), [200, 404]);
    }
}
