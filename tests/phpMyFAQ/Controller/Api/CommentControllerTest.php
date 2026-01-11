<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
class CommentControllerTest extends TestCase
{

    /**
     * @throws Exception
     */
    public function testListReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->attributes->set('recordId', '1');

        $controller = new CommentController();
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testListReturnsValidStatusCode(): void
    {
        $request = new Request();
        $request->attributes->set('recordId', '1');

        $controller = new CommentController();
        $response = $controller->list($request);

        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }

    /**
     * @throws Exception
     */
    public function testListWithNonExistentRecordId(): void
    {
        $request = new Request();
        $request->attributes->set('recordId', '999999');

        $controller = new CommentController();
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testListReturnsJsonData(): void
    {
        $request = new Request();
        $request->attributes->set('recordId', '1');

        $controller = new CommentController();
        $response = $controller->list($request);

        $this->assertJson($response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testListReturnsArrayData(): void
    {
        $request = new Request();
        $request->attributes->set('recordId', '1');

        $controller = new CommentController();
        $response = $controller->list($request);

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    /**
     * @throws Exception
     */
    public function testListWithInvalidRecordId(): void
    {
        $request = new Request();
        $request->attributes->set('recordId', 'invalid');

        $controller = new CommentController();
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testListWithZeroRecordId(): void
    {
        $request = new Request();
        $request->attributes->set('recordId', '0');

        $controller = new CommentController();
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testListResponseContentIsNotNull(): void
    {
        $request = new Request();
        $request->attributes->set('recordId', '1');

        $controller = new CommentController();
        $response = $controller->list($request);

        $this->assertNotNull($response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testListReturnsEmptyArrayOn404(): void
    {
        $request = new Request();
        $request->attributes->set('recordId', '999999');

        $controller = new CommentController();
        $response = $controller->list($request);

        if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
            $this->assertEquals([], json_decode($response->getContent(), true));
        } else {
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        }
    }

    /**
     * @throws Exception
     */
    public function testListWithNegativeRecordId(): void
    {
        $request = new Request();
        $request->attributes->set('recordId', '-1');

        $controller = new CommentController();
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testListWithLargeRecordId(): void
    {
        $request = new Request();
        $request->attributes->set('recordId', '999999999');

        $controller = new CommentController();
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }
}
