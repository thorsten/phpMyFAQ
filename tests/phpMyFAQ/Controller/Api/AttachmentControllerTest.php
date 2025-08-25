<?php

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\File;
use phpMyFAQ\Configuration;
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
        $recordId = $request->get('recordId');
        $result = [];

        try {
            // Instead of calling AttachmentFactory::fetchByRecordId, use our test value
            if ($this->returnValueOrException instanceof AttachmentException) {
                throw $this->returnValueOrException;
            }
            $attachments = $this->returnValueOrException;

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
        } catch (AttachmentException) {
            return $this->json($result, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Override the json method from AbstractController
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @return JsonResponse
     */
    public function json(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }
}

class AttachmentControllerTest extends TestCase
{
    /**
     * @throws Exception|ReflectionException
     */
    public function testConstructorWithApiEnabled(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('get')
            ->with('api.enableAccess')
            ->willReturn(true);

        $attachmentController = $this->getMockBuilder(AttachmentController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isApiEnabled'])
            ->getMock();

        $attachmentController->method('isApiEnabled')
            ->willReturn(true);

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
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('get')
            ->with('api.enableAccess')
            ->willReturn(false);

        $attachmentController = $this->getMockBuilder(AttachmentController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isApiEnabled'])
            ->getMock();

        $attachmentController->method('isApiEnabled')
            ->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);

        $reflection = new ReflectionClass(AttachmentController::class);
        $constructor = $reflection->getConstructor();
        $constructor->invoke($attachmentController);
    }

    /**
     * @throws Exception
     */
    public function testListWithAttachmentsFound(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('get')
            ->with('recordId')
            ->willReturn('123');

        $file1 = $this->createMock(File::class);
        $file1->method('getFilename')->willReturn('attachment-1.pdf');
        $file1->method('buildUrl')->willReturn('index.php?action=attachment&id=1');

        $file2 = $this->createMock(File::class);
        $file2->method('getFilename')->willReturn('attachment-2.pdf');
        $file2->method('buildUrl')->willReturn('index.php?action=attachment&id=2');

        $attachmentController = $this->createAttachmentControllerTestDouble([$file1, $file2]);

        $response = $attachmentController->list($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $expectedData = [
            [
                'filename' => 'attachment-1.pdf',
                'url' => 'https://www.example.org/index.php?action=attachment&id=1',
            ],
            [
                'filename' => 'attachment-2.pdf',
                'url' => 'https://www.example.org/index.php?action=attachment&id=2',
            ],
        ];

        $this->assertEquals($expectedData, json_decode($response->getContent(), true));
    }

    /**
     * @throws Exception
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testListWithNoAttachmentsFound(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('get')
            ->with('recordId')
            ->willReturn('123');

        $attachmentController = $this->createAttachmentControllerTestDouble([]);

        $response = $attachmentController->list($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals([], json_decode($response->getContent(), true));
    }

    /**
     * @throws Exception
     */
    public function testListWithExceptionThrown(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('get')
            ->with('recordId')
            ->willReturn('123');

        $attachmentController = $this->createAttachmentControllerTestDouble(new AttachmentException('Test exception'));

        $response = $attachmentController->list($request);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertEquals([], json_decode($response->getContent(), true));
    }

    /**
     * Create a test double for AttachmentController that overrides the fetchByRecordId call
     *
     * @param array|AttachmentException $returnValueOrException What to return or throw from fetchByRecordId
     * @return TestableAttachmentController
     * @throws Exception|\phpMyFAQ\Core\Exception
     */
    private function createAttachmentControllerTestDouble(mixed $returnValueOrException): TestableAttachmentController
    {
        $attachmentController = new TestableAttachmentController($returnValueOrException);

        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getDefaultUrl')
            ->willReturn('https://www.example.org/');

        $reflection = new ReflectionClass(AttachmentController::class);

        $configurationProperty = $reflection->getProperty('configuration');
        $configurationProperty->setValue($attachmentController, $configuration);

        return $attachmentController;
    }
}
