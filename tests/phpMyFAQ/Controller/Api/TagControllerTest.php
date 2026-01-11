<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
class TagControllerTest extends TestCase
{
    public function testListReturnsJsonResponse(): void
    {
        $controller = new TagController();
        $response = $controller->list();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testListReturnsValidStatusCode(): void
    {
        $controller = new TagController();
        $response = $controller->list();

        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }

    public function testListReturnsJsonData(): void
    {
        $controller = new TagController();
        $response = $controller->list();

        $this->assertJson($response->getContent());
    }

    public function testListReturnsArrayData(): void
    {
        $controller = new TagController();
        $response = $controller->list();

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testListResponseContentIsNotNull(): void
    {
        $controller = new TagController();
        $response = $controller->list();

        $this->assertNotNull($response->getContent());
    }

    public function testListReturnsCorrectStructureWhenTagsExist(): void
    {
        $controller = new TagController();
        $response = $controller->list();

        if ($response->getStatusCode() === Response::HTTP_OK) {
            $data = json_decode($response->getContent(), true);
            $this->assertNotEmpty($data);

            foreach ($data as $tag) {
                $this->assertArrayHasKey('tagId', $tag);
                $this->assertArrayHasKey('tagName', $tag);
                $this->assertArrayHasKey('tagFrequency', $tag);
            }
        } else {
            $this->assertEquals([], json_decode($response->getContent(), true));
        }
    }

    public function testListReturnsEmptyArrayOn404(): void
    {
        $controller = new TagController();
        $response = $controller->list();

        if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
            $this->assertEquals([], json_decode($response->getContent(), true));
        } else {
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        }
    }
}
