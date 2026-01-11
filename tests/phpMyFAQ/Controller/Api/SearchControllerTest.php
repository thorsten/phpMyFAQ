<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class SearchControllerTest extends TestCase
{
    public function testSearchReturnsJsonResponse(): void
    {
        $request = new Request(['q' => 'test']);
        $controller = new SearchController();
        $response = $controller->search($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testSearchReturnsValidStatusCode(): void
    {
        $request = new Request(['q' => 'test']);
        $controller = new SearchController();
        $response = $controller->search($request);

        $this->assertContains($response->getStatusCode(), [200, 404, 500]);
    }

    public function testPopularReturnsJsonResponse(): void
    {
        $controller = new SearchController();
        $response = $controller->popular();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testPopularReturnsValidStatusCode(): void
    {
        $controller = new SearchController();
        $response = $controller->popular();

        $this->assertContains($response->getStatusCode(), [200, 404]);
    }
}
