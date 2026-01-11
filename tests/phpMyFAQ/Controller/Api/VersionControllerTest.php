<?php

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\System;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

#[AllowMockObjectsWithoutExpectations]
class VersionControllerTest extends TestCase
{
    public function testIndex(): void
    {
        $versionNumber = System::getVersion();

        $versionController = new VersionController();

        $response = $versionController->index();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($versionNumber, json_decode($response->getContent(), true));
    }

    public function testIndexReturnsJsonResponse(): void
    {
        $versionController = new VersionController();
        $response = $versionController->index();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertJson($response->getContent());
    }

    public function testIndexReturnsValidVersionFormat(): void
    {
        $versionController = new VersionController();
        $response = $versionController->index();

        $version = json_decode($response->getContent(), true);
        $this->assertNotEmpty($version);
        $this->assertIsString($version);
        // Version should match semantic versioning pattern
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', $version);
    }

    public function testIndexResponseContentIsNotNull(): void
    {
        $versionController = new VersionController();
        $response = $versionController->index();

        $this->assertNotNull($response->getContent());
    }
}
