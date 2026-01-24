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
}
