<?php

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

#[AllowMockObjectsWithoutExpectations]
class TitleControllerTest extends TestCase
{
    public function testIndex(): void
    {
        $title = Configuration::getConfigurationInstance()->getTitle();

        $titleController = new TitleController();

        $response = $titleController->index();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($title, json_decode($response->getContent(), true));
    }

    public function testIndexReturnsJsonResponse(): void
    {
        $titleController = new TitleController();
        $response = $titleController->index();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertJson($response->getContent());
    }

    public function testIndexReturnsNonEmptyTitle(): void
    {
        $titleController = new TitleController();
        $response = $titleController->index();

        $title = json_decode($response->getContent(), true);
        $this->assertNotEmpty($title);
        $this->assertIsString($title);
    }

    public function testIndexResponseContentIsNotNull(): void
    {
        $titleController = new TitleController();
        $response = $titleController->index();

        $this->assertNotNull($response->getContent());
    }
}
