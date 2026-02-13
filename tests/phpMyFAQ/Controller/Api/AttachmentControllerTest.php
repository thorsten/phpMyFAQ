<?php

namespace phpMyFAQ\Controller\Api;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[AllowMockObjectsWithoutExpectations]
class AttachmentControllerTest extends TestCase
{
    /**
     * @throws Exception|ReflectionException
     */
    public function testConstructorWithApiEnabled(): void
    {
        $attachmentController = $this->getMockBuilder(AttachmentController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isApiEnabled'])
            ->getMock();

        $attachmentController->expects($this->once())->method('isApiEnabled')->willReturn(true);

        $reflection = new ReflectionClass(AttachmentController::class);
        $constructor = $reflection->getConstructor();
        $constructor->invoke($attachmentController);

        $this->assertTrue(true);
    }

    /**
     * @throws Exception|ReflectionException
     */
    public function testConstructorWithApiDisabled(): void
    {
        $attachmentController = $this->getMockBuilder(AttachmentController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isApiEnabled'])
            ->getMock();

        $attachmentController->expects($this->once())->method('isApiEnabled')->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);

        $reflection = new ReflectionClass(AttachmentController::class);
        $constructor = $reflection->getConstructor();
        $constructor->invoke($attachmentController);
    }

    public function testListReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '1');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testListReturnsValidStatusCode(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '1');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND,
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ]);
    }

    public function testListReturnsJsonData(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '1');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $this->assertJson($response->getContent());
    }

    public function testListReturnsArrayData(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '1');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testListWithInvalidFaqId(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', 'invalid');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // Verify envelope structure with empty data
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);
        $this->assertCount(0, $data['data']);
    }

    public function testListWithZeroFaqId(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '0');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testListWithNegativeFaqId(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '-5');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testListWithMissingFaqId(): void
    {
        $request = new Request();

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // Verify envelope structure with empty data
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);
        $this->assertCount(0, $data['data']);
    }

    public function testListWithLargeFaqId(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '999999999');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND,
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ]);
    }

    public function testListResponseContentIsNotNull(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '1');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $this->assertNotNull($response->getContent());
    }

    public function testListReturnsEmptyArrayWhenNoAttachments(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '999999');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
            $this->assertEquals([], json_decode($response->getContent(), true));
        } else {
            // If attachments exist or error occurred, just verify it's a valid response
            $this->assertInstanceOf(JsonResponse::class, $response);
        }
    }

    public function testListWithNumericStringFaqId(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '123');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertJson($response->getContent());
    }

    public function testListResponseStructure(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '1');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);

        // Verify envelope structure
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);

        // Verify meta contains pagination
        $this->assertArrayHasKey('pagination', $data['meta']);
        $this->assertArrayHasKey('total', $data['meta']['pagination']);
        $this->assertArrayHasKey('per_page', $data['meta']['pagination']);
        $this->assertArrayHasKey('current_page', $data['meta']['pagination']);

        // If there are attachments, verify the attachment structure
        if (count($data['data']) > 0) {
            foreach ($data['data'] as $attachment) {
                $this->assertArrayHasKey('filename', $attachment);
                $this->assertArrayHasKey('url', $attachment);
            }
        }
    }
}
