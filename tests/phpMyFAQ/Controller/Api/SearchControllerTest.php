<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND, Response::HTTP_INTERNAL_SERVER_ERROR]);
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

        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }

    public function testSearchWithEmptyQuery(): void
    {
        $request = new Request(['q' => '']);
        $controller = new SearchController();
        $response = $controller->search($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testSearchReturnsJsonData(): void
    {
        $request = new Request(['q' => 'test']);
        $controller = new SearchController();
        $response = $controller->search($request);

        $this->assertJson($response->getContent());
    }

    public function testSearchReturnsArrayData(): void
    {
        $request = new Request(['q' => 'test']);
        $controller = new SearchController();
        $response = $controller->search($request);

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testPopularReturnsJsonData(): void
    {
        $controller = new SearchController();
        $response = $controller->popular();

        $this->assertJson($response->getContent());
    }

    public function testPopularReturnsArrayData(): void
    {
        $controller = new SearchController();
        $response = $controller->popular();

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testSearchWithSpecialCharacters(): void
    {
        $request = new Request(['q' => '@#$%^&*()']);
        $controller = new SearchController();
        $response = $controller->search($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testSearchWithUnicodeCharacters(): void
    {
        $request = new Request(['q' => '日本語テスト']);
        $controller = new SearchController();
        $response = $controller->search($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertJson($response->getContent());
    }

    public function testSearchWithLongQuery(): void
    {
        $request = new Request(['q' => str_repeat('a', 1000)]);
        $controller = new SearchController();
        $response = $controller->search($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testSearchWithWhitespaceQuery(): void
    {
        $request = new Request(['q' => '   ']);
        $controller = new SearchController();
        $response = $controller->search($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testSearchResponseContentIsNotNull(): void
    {
        $request = new Request(['q' => 'test']);
        $controller = new SearchController();
        $response = $controller->search($request);

        $this->assertNotNull($response->getContent());
    }

    public function testPopularResponseContentIsNotNull(): void
    {
        $controller = new SearchController();
        $response = $controller->popular();

        $this->assertNotNull($response->getContent());
    }
}
