<?php

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

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
}
