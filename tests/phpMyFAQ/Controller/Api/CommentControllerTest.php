<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use Exception;use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class CommentControllerTest extends TestCase
{
    /**
     * @throws Exception
     */public function testListReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->attributes->set('recordId', '1');

        $controller = new CommentController();
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */public function testListReturnsValidStatusCode(): void
    {
        $request = new Request();
        $request->attributes->set('recordId', '1');

        $controller = new CommentController();
        $response = $controller->list($request);

        $this->assertContains($response->getStatusCode(), [200, 404]);
    }
}
