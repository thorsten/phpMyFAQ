<?php

namespace phpMyFAQ\Controller\Api;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(HealthController::class)]
#[UsesNamespace('phpMyFAQ')]
class HealthControllerTest extends TestCase
{
    public function testIndexReportsOk(): void
    {
        $response = new HealthController()->index();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['status' => 'ok'], json_decode((string) $response->getContent(), true));
    }

    public function testIndexIsNeverCached(): void
    {
        $response = new HealthController()->index();

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }
}
