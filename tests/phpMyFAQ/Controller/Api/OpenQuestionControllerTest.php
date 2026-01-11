<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
class OpenQuestionControllerTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testListReturnsJsonResponse(): void
    {
        $controller = new OpenQuestionController();
        $response = $controller->list();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testListReturnsValidStatusCode(): void
    {
        $controller = new OpenQuestionController();
        $response = $controller->list();

        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }

    /**
     * @throws Exception
     */
    public function testListReturnsJsonData(): void
    {
        $controller = new OpenQuestionController();
        $response = $controller->list();

        $this->assertJson($response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testListReturnsArrayData(): void
    {
        $controller = new OpenQuestionController();
        $response = $controller->list();

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    /**
     * @throws Exception
     */
    public function testListResponseContentIsNotNull(): void
    {
        $controller = new OpenQuestionController();
        $response = $controller->list();

        $this->assertNotNull($response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testListReturnsEmptyArrayOn404(): void
    {
        $controller = new OpenQuestionController();
        $response = $controller->list();

        if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
            $this->assertEquals([], json_decode($response->getContent(), true));
        } else {
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        }
    }
}
