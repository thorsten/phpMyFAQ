<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use Exception;
use phpMyFAQ\Comments;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Faq;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(CommentController::class)]
#[UsesNamespace('phpMyFAQ')]
class CommentControllerTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testListReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->attributes->set('recordId', '1');

        $controller = new CommentController($this->createStub(Comments::class));
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

        $controller = new CommentController($this->createStub(Comments::class));
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

        $controller = new CommentController($this->createStub(Comments::class));
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

        $controller = new CommentController($this->createStub(Comments::class));
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

        $controller = new CommentController($this->createStub(Comments::class));
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

        $controller = new CommentController($this->createStub(Comments::class));
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

        $controller = new CommentController($this->createStub(Comments::class));
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

        $controller = new CommentController($this->createStub(Comments::class));
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

        $controller = new CommentController($this->createStub(Comments::class));
        $response = $controller->list($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue($data['success']);

        // Data can be empty array if no comments exist
        $this->assertIsArray($data['data']);
    }

    /**
     * @throws Exception
     */
    public function testListWithNegativeRecordId(): void
    {
        $request = new Request();
        $request->attributes->set('recordId', '-1');

        $controller = new CommentController($this->createStub(Comments::class));
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

        $controller = new CommentController($this->createStub(Comments::class));
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }

    /**
     * Security regression: an anonymous requester who cannot see the parent FAQ
     * must not receive its comments. The controller must short-circuit with a
     * 404 before reading any comment data.
     *
     * @throws Exception
     */
    public function testListReturnsNotFoundAndDoesNotReadCommentsWhenFaqIsNotAccessible(): void
    {
        $request = new Request();
        $request->attributes->set('recordId', '123');

        $faq = $this->createMock(Faq::class);
        $faq->method('setUser')->willReturnSelf();
        $faq->method('setGroups')->willReturnSelf();
        $faq->expects($this->once())->method('isFaqAccessibleForUser')->with(123)->willReturn(false);

        $comments = $this->createMock(Comments::class);
        $comments->expects($this->never())->method('getCommentsDataPaginated');
        $comments->expects($this->never())->method('countComments');

        $controller = new CommentController($comments);

        // Delegate every service except the mocked Faq to the controller's real container.
        $realContainer = new ReflectionProperty(AbstractController::class, 'container')->getValue($controller);
        $this->assertInstanceOf(ContainerInterface::class, $realContainer);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(
                static fn(string $id): mixed => $id === 'phpmyfaq.faq' ? $faq : $realContainer->get($id),
            );
        $container
            ->method('has')
            ->willReturnCallback(static fn(string $id): bool => $realContainer->has($id));
        $controller->setContainer($container);

        $response = $controller->list($request);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame([], json_decode($response->getContent(), true));
    }
}
