<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Api\Filtering\FilterRequest;
use phpMyFAQ\Api\Pagination\PaginationMetadata;
use phpMyFAQ\Api\Pagination\PaginationRequest;
use phpMyFAQ\Api\Response\ApiResponse;
use phpMyFAQ\Api\Sorting\SortRequest;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Filter;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(AbstractApiController::class)]
#[UsesClass(AbstractController::class)]
#[UsesClass(\phpMyFAQ\Twig\TwigWrapper::class)]
#[UsesClass(Filter::class)]
#[UsesClass(PaginationRequest::class)]
#[UsesClass(PaginationMetadata::class)]
#[UsesClass(SortRequest::class)]
#[UsesClass(FilterRequest::class)]
#[UsesClass(ApiResponse::class)]
#[UsesClass(PaginatedResponseOptions::class)]
final class AbstractApiControllerTest extends TestCase
{
    public function testSetContainerThrowsWhenApiIsDisabled(): void
    {
        $controller = new AbstractApiControllerTestStub();

        $this->expectException(UnauthorizedHttpException::class);
        $controller->setContainer($this->createControllerContainer(enableApi: false));
    }

    public function testGetPaginationRequestParsesPageBasedQuery(): void
    {
        $controller = new AbstractApiControllerTestStub();
        $request = new Request(['page' => '3', 'per_page' => '10']);

        $pagination = $controller->getPaginationRequestPublic($request);

        self::assertSame(10, $pagination->limit);
        self::assertSame(20, $pagination->offset);
        self::assertSame(3, $pagination->page);
        self::assertTrue($pagination->isPageBased);
        self::assertFalse($pagination->isOffsetBased);
    }

    public function testGetSortRequestAppliesDefaultsAndWhitelist(): void
    {
        $controller = new AbstractApiControllerTestStub();
        $request = new Request(['sort' => 'ignored', 'order' => 'descending']);

        $sort = $controller->getSortRequestPublic($request, ['name', 'created'], 'name');

        self::assertInstanceOf(SortRequest::class, $sort);
        self::assertSame('name', $sort->getField());
        self::assertSame('desc', $sort->getOrder());
    }

    public function testGetFilterRequestParsesDirectAndNestedFilters(): void
    {
        $controller = new AbstractApiControllerTestStub();
        $request = new Request([
            'active' => 'true',
            'filter' => ['category_id' => '12'],
        ]);

        $filters = $controller->getFilterRequestPublic($request, [
            'active' => 'bool',
            'category_id' => 'int',
        ]);

        self::assertInstanceOf(FilterRequest::class, $filters);
        self::assertTrue($filters->has('active'));
        self::assertTrue($filters->get('active'));
        self::assertSame(12, $filters->get('category_id'));
    }

    public function testPaginatedResponseReturnsEnvelopeWithMetadata(): void
    {
        $controller = new AbstractApiControllerTestStub();
        $request = new Request(['page' => '2', 'per_page' => '2'], [], [], [], [], ['REQUEST_URI' => '/api/items']);
        $pagination = $controller->getPaginationRequestPublic($request);
        $sort = $controller->getSortRequestPublic(new Request(['sort' => 'name']), ['name'], 'name');
        $filters = $controller->getFilterRequestPublic(new Request(['active' => 'true']), ['active' => 'bool']);
        $options = new PaginatedResponseOptions(sort: $sort, filters: $filters, status: Response::HTTP_ACCEPTED);

        $response = $controller->paginatedResponsePublic(
            $request,
            [['id' => 1], ['id' => 2]],
            5,
            $pagination,
            $options,
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
        self::assertTrue($payload['success']);
        self::assertCount(2, $payload['data']);
        self::assertSame(5, $payload['meta']['pagination']['total']);
        self::assertSame('name', $payload['meta']['sorting']['field']);
        self::assertTrue($payload['meta']['filters']['active']);
    }

    public function testApiResponseReturnsSuccessEnvelope(): void
    {
        $controller = new AbstractApiControllerTestStub();

        $response = $controller->apiResponsePublic(['id' => 42]);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($payload['success']);
        self::assertSame(42, $payload['data']['id']);
    }

    public function testErrorResponseReturnsErrorEnvelope(): void
    {
        $controller = new AbstractApiControllerTestStub();

        $response = $controller->errorResponsePublic('Bad input', 'INVALID', Response::HTTP_CONFLICT, [
            'field' => 'name',
        ]);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertFalse($payload['success']);
        self::assertSame('INVALID', $payload['error']['code']);
        self::assertSame('Bad input', $payload['error']['message']);
        self::assertSame('name', $payload['error']['details']['field']);
        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    private function createControllerContainer(bool $enableApi): ContainerInterface
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getTemplateSet')->willReturn('default');
        $configuration
            ->method('get')
            ->willReturnCallback(static fn(string $item) => match ($item) {
                'security.enableLoginOnly' => false,
                'api.enableAccess' => $enableApi,
                default => null,
            });

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(false);

        $session = $this->createStub(SessionInterface::class);

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(static function (string $id) use ($configuration, $currentUser, $session) {
                return match ($id) {
                    'phpmyfaq.configuration' => $configuration,
                    'phpmyfaq.user.current_user' => $currentUser,
                    'session' => $session,
                    default => null,
                };
            });

        return $container;
    }
}

final class AbstractApiControllerTestStub extends AbstractApiController
{
    public function __construct()
    {
    }

    public function getPaginationRequestPublic(
        Request $request,
        int $defaultPerPage = self::DEFAULT_PER_PAGE,
        ?int $maxPerPage = null,
    ): \phpMyFAQ\Api\Pagination\PaginationRequest {
        return $this->getPaginationRequest($request, $defaultPerPage, $maxPerPage);
    }

    public function getSortRequestPublic(
        Request $request,
        array $allowedFields,
        ?string $defaultField = null,
        string $defaultOrder = 'asc',
    ): SortRequest {
        return $this->getSortRequest($request, $allowedFields, $defaultField, $defaultOrder);
    }

    public function getFilterRequestPublic(Request $request, array $allowedFilters): FilterRequest
    {
        return $this->getFilterRequest($request, $allowedFilters);
    }

    public function paginatedResponsePublic(
        Request $request,
        array $data,
        int $total,
        \phpMyFAQ\Api\Pagination\PaginationRequest $pagination,
        ?PaginatedResponseOptions $options = null,
    ): \Symfony\Component\HttpFoundation\JsonResponse {
        return $this->paginatedResponse($request, $data, $total, $pagination, $options);
    }

    public function apiResponsePublic(
        array|object $data,
        int $status = Response::HTTP_OK,
    ): \Symfony\Component\HttpFoundation\JsonResponse {
        return $this->apiResponse($data, $status);
    }

    public function errorResponsePublic(
        string $message,
        string $code = 'ERROR',
        int $status = Response::HTTP_BAD_REQUEST,
        ?array $details = null,
    ): \Symfony\Component\HttpFoundation\JsonResponse {
        return $this->errorResponse($message, $code, $status, $details);
    }
}
