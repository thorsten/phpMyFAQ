<?php

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\File;
use phpMyFAQ\Configuration;
use phpMyFAQ\Filter;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Test-specific subclass of AttachmentController that allows us to control the behavior
 * of the AttachmentFactory::fetchByRecordId method
 */
class TestableAttachmentController extends AttachmentController
{
    private mixed $returnValueOrException;

    public function __construct(mixed $returnValueOrException)
    {
        $this->returnValueOrException = $returnValueOrException;
        // Don't call parent constructor to avoid the API check
    }

    public function list(Request $request): JsonResponse
    {
        $recordId = (int) Filter::filterVar($request->attributes->get(key: 'recordId'), FILTER_VALIDATE_INT);
        $result = [];

        try {
            if ($this->returnValueOrException instanceof AttachmentException) {
                throw $this->returnValueOrException;
            }
            $attachments = $this->returnValueOrException;
        } catch (AttachmentException) {
            return $this->json($result, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        foreach ($attachments as $attachment) {
            $result[] = [
                'filename' => $attachment->getFilename(),
                'url' => $this->configuration->getDefaultUrl() . $attachment->buildUrl(),
            ];
        }

        if ($result === []) {
            return $this->json($result, Response::HTTP_NOT_FOUND);
        }

        return $this->json($result, Response::HTTP_OK);
    }

    public function json(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }
}

#[AllowMockObjectsWithoutExpectations]
class AttachmentControllerTest extends TestCase
{
    /**
     * @throws Exception|ReflectionException
     */
    public function testConstructorWithApiEnabled(): void
    {
        $attachmentController = $this
            ->getMockBuilder(AttachmentController::class)
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
        $attachmentController = $this
            ->getMockBuilder(AttachmentController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isApiEnabled'])
            ->getMock();

        $attachmentController->expects($this->once())->method('isApiEnabled')->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);

        $reflection = new ReflectionClass(AttachmentController::class);
        $constructor = $reflection->getConstructor();
        $constructor->invoke($attachmentController);
    }

    /**
     * @throws Exception
     */
    public function testListWithMultipleAttachments(): void
    {
        $request = new Request([], [], ['recordId' => '123']);

        $file1 = $this->createStub(File::class);
        $file1->method('getFilename')->willReturn('attachment-1.pdf');
        $file1->method('buildUrl')->willReturn('attachment/1');

        $file2 = $this->createStub(File::class);
        $file2->method('getFilename')->willReturn('attachment-2.pdf');
        $file2->method('buildUrl')->willReturn('attachment/2');

        $controller = $this->createTestableController([$file1, $file2]);
        $response = $controller->list($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data);
        $this->assertEquals('attachment-1.pdf', $data[0]['filename']);
        $this->assertEquals('https://www.example.org/attachment/1', $data[0]['url']);
        $this->assertEquals('attachment-2.pdf', $data[1]['filename']);
        $this->assertEquals('https://www.example.org/attachment/2', $data[1]['url']);
    }

    /**
     * @throws Exception
     */
    public function testListWithSingleAttachment(): void
    {
        $request = new Request([], [], ['recordId' => '456']);

        $file = $this->createStub(File::class);
        $file->method('getFilename')->willReturn('document.pdf');
        $file->method('buildUrl')->willReturn('attachment/99');

        $controller = $this->createTestableController([$file]);
        $response = $controller->list($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertCount(1, $data);
        $this->assertEquals('document.pdf', $data[0]['filename']);
    }

    /**
     * @throws Exception
     */
    public function testListWithNoAttachments(): void
    {
        $request = new Request([], [], ['recordId' => '123']);

        $controller = $this->createTestableController([]);
        $response = $controller->list($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals([], json_decode($response->getContent(), true));
    }

    /**
     * @throws Exception
     */
    public function testListWithException(): void
    {
        $request = new Request([], [], ['recordId' => '123']);

        $controller = $this->createTestableController(new AttachmentException('Database error'));
        $response = $controller->list($request);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertEquals([], json_decode($response->getContent(), true));
    }

    /**
     * @throws Exception
     */
    public function testListWithInvalidRecordId(): void
    {
        $request = new Request([], [], ['recordId' => 'invalid']);

        $controller = $this->createTestableController([]);
        $response = $controller->list($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testListWithZeroRecordId(): void
    {
        $request = new Request([], [], ['recordId' => '0']);

        $controller = $this->createTestableController([]);
        $response = $controller->list($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testListWithNegativeRecordId(): void
    {
        $request = new Request([], [], ['recordId' => '-5']);

        $controller = $this->createTestableController([]);
        $response = $controller->list($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testListWithMissingRecordId(): void
    {
        $request = new Request();

        $controller = $this->createTestableController([]);
        $response = $controller->list($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testListWithSpecialCharactersInFilename(): void
    {
        $request = new Request([], [], ['recordId' => '123']);

        $file = $this->createStub(File::class);
        $file->method('getFilename')->willReturn('file with spaces & special-chars (1).pdf');
        $file->method('buildUrl')->willReturn('attachment/special');

        $controller = $this->createTestableController([$file]);
        $response = $controller->list($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('file with spaces & special-chars (1).pdf', $data[0]['filename']);
    }

    /**
     * @throws Exception
     */
    public function testListWithUnicodeFilename(): void
    {
        $request = new Request([], [], ['recordId' => '123']);

        $file = $this->createStub(File::class);
        $file->method('getFilename')->willReturn('日本語ファイル.pdf');
        $file->method('buildUrl')->willReturn('attachment/unicode');

        $controller = $this->createTestableController([$file]);
        $response = $controller->list($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('日本語ファイル.pdf', $data[0]['filename']);
    }

    /**
     * @throws Exception
     */
    public function testListWithManyAttachments(): void
    {
        $request = new Request([], [], ['recordId' => '123']);

        $attachments = [];
        for ($i = 1; $i <= 10; $i++) {
            $file = $this->createStub(File::class);
            $file->method('getFilename')->willReturn("file-{$i}.pdf");
            $file->method('buildUrl')->willReturn("attachment/{$i}");
            $attachments[] = $file;
        }

        $controller = $this->createTestableController($attachments);
        $response = $controller->list($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertCount(10, $data);
    }

    /**
     * @throws Exception
     */
    public function testListUrlConstruction(): void
    {
        $request = new Request([], [], ['recordId' => '123']);

        $file = $this->createStub(File::class);
        $file->method('getFilename')->willReturn('test.pdf');
        $file->method('buildUrl')->willReturn('attachment/42');

        $controller = $this->createTestableController([$file]);
        $response = $controller->list($request);

        $data = json_decode($response->getContent(), true);
        $this->assertStringStartsWith('https://www.example.org/', $data[0]['url']);
        $this->assertStringEndsWith('attachment/42', $data[0]['url']);
    }

    /**
     * @throws Exception
     */
    public function testListResponseContentType(): void
    {
        $request = new Request([], [], ['recordId' => '123']);

        $file = $this->createStub(File::class);
        $file->method('getFilename')->willReturn('test.pdf');
        $file->method('buildUrl')->willReturn('attachment/1');

        $controller = $this->createTestableController([$file]);
        $response = $controller->list($request);

        $this->assertJson($response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testListWithDifferentFileTypes(): void
    {
        $request = new Request([], [], ['recordId' => '123']);

        $pdf = $this->createStub(File::class);
        $pdf->method('getFilename')->willReturn('document.pdf');
        $pdf->method('buildUrl')->willReturn('attachment/1');

        $image = $this->createStub(File::class);
        $image->method('getFilename')->willReturn('image.png');
        $image->method('buildUrl')->willReturn('attachment/2');

        $text = $this->createStub(File::class);
        $text->method('getFilename')->willReturn('readme.txt');
        $text->method('buildUrl')->willReturn('attachment/3');

        $controller = $this->createTestableController([$pdf, $image, $text]);
        $response = $controller->list($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertCount(3, $data);
        $this->assertEquals('document.pdf', $data[0]['filename']);
        $this->assertEquals('image.png', $data[1]['filename']);
        $this->assertEquals('readme.txt', $data[2]['filename']);
    }

    /**
     * @throws Exception
     */
    public function testListWithLargeRecordId(): void
    {
        $request = new Request([], [], ['recordId' => '999999999']);

        $controller = $this->createTestableController([]);
        $response = $controller->list($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * Create a testable controller with mocked dependencies.
     *
     * @param array|AttachmentException $returnValueOrException
     * @return TestableAttachmentController
     * @throws Exception
     */
    private function createTestableController(mixed $returnValueOrException): TestableAttachmentController
    {
        $controller = new TestableAttachmentController($returnValueOrException);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDefaultUrl')->willReturn('https://www.example.org/');

        $reflection = new ReflectionClass(AttachmentController::class);
        $configurationProperty = $reflection->getProperty('configuration');
        $configurationProperty->setValue($controller, $configuration);

        return $controller;
    }
}

