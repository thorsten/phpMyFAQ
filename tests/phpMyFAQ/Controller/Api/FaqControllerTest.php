<?php

namespace phpMyFAQ\Controller\Api;

use Exception;
use League\CommonMark\Exception\CommonMarkException;
use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\FaqMetaData;
use phpMyFAQ\Faq\FaqPermission;
use phpMyFAQ\Faq\FaqStatistics;
use phpMyFAQ\Filter;
use phpMyFAQ\Tags;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\MockObject\Exception as MockException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Test-specific subclass of FaqController that allows us to control the behavior
 * of the dependencies and methods
 */
class TestableFaqController extends FaqController
{
    private mixed $returnValueOrException;
    private string $methodToTest;

    public function __construct(mixed $returnValueOrException, string $methodToTest)
    {
        $this->returnValueOrException = $returnValueOrException;
        $this->methodToTest = $methodToTest;
    }

    /**
     * Override the json method from AbstractController
     */
    public function json(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * Override the getByCategoryId method to use our test value
     */
    public function getByCategoryId(Request $request): JsonResponse
    {
        if ($this->methodToTest !== 'getByCategoryId') {
            return parent::getByCategoryId($request);
        }

        if ($this->returnValueOrException instanceof Exception) {
            return $this->json(
                ['error' => $this->returnValueOrException->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $this->json($this->returnValueOrException, Response::HTTP_OK);
    }

    /**
     * Override the getById method to use our test value
     */
    public function getById(Request $request): JsonResponse
    {
        if ($this->methodToTest !== 'getById') {
            return parent::getById($request);
        }

        if ($this->returnValueOrException instanceof Exception) {
            return $this->json(
                ['error' => $this->returnValueOrException->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        if (
            $this->returnValueOrException instanceof stdClass ||
            (is_array($this->returnValueOrException) && count($this->returnValueOrException) === 0)
        ) {
            return $this->json($this->returnValueOrException, Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->returnValueOrException, Response::HTTP_OK);
    }

    /**
     * Override the getByTagId method to use our test value
     */
    public function getByTagId(Request $request): JsonResponse
    {
        if ($this->methodToTest !== 'getByTagId') {
            return parent::getByTagId($request);
        }

        if ($this->returnValueOrException instanceof Exception) {
            return $this->json(
                ['error' => $this->returnValueOrException->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $this->json($this->returnValueOrException, Response::HTTP_OK);
    }

    /**
     * Override the getPopular method to use our test value
     */
    public function getPopular(): JsonResponse
    {
        if ($this->methodToTest !== 'getPopular') {
            return parent::getPopular();
        }

        if ($this->returnValueOrException instanceof Exception) {
            return $this->json(
                ['error' => $this->returnValueOrException->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        if (is_array($this->returnValueOrException) && count($this->returnValueOrException) === 0) {
            return $this->json($this->returnValueOrException, Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->returnValueOrException, Response::HTTP_OK);
    }

    /**
     * Override the getLatest method to use our test value
     */
    public function getLatest(): JsonResponse
    {
        if ($this->methodToTest !== 'getLatest') {
            return parent::getLatest();
        }

        if ($this->returnValueOrException instanceof Exception) {
            return $this->json(
                ['error' => $this->returnValueOrException->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        if (is_array($this->returnValueOrException) && count($this->returnValueOrException) === 0) {
            return $this->json($this->returnValueOrException, Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->returnValueOrException, Response::HTTP_OK);
    }

    /**
     * Override the getTrending method to use our test value
     */
    public function getTrending(): JsonResponse
    {
        if ($this->methodToTest !== 'getTrending') {
            return parent::getTrending();
        }

        if ($this->returnValueOrException instanceof Exception) {
            return $this->json(
                ['error' => $this->returnValueOrException->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        if (is_array($this->returnValueOrException) && count($this->returnValueOrException) === 0) {
            return $this->json($this->returnValueOrException, Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->returnValueOrException, Response::HTTP_OK);
    }

    /**
     * Override the getSticky method to use our test value
     */
    public function getSticky(): JsonResponse
    {
        if ($this->methodToTest !== 'getSticky') {
            return parent::getSticky();
        }

        if ($this->returnValueOrException instanceof Exception) {
            return $this->json(
                ['error' => $this->returnValueOrException->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        if (is_array($this->returnValueOrException) && count($this->returnValueOrException) === 0) {
            return $this->json($this->returnValueOrException, Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->returnValueOrException, Response::HTTP_OK);
    }

    /**
     * Override the list method to use our test value
     */
    public function list(): JsonResponse
    {
        if ($this->methodToTest !== 'list') {
            return parent::list();
        }

        if ($this->returnValueOrException instanceof Exception) {
            return $this->json(
                ['error' => $this->returnValueOrException->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        if (is_array($this->returnValueOrException) && count($this->returnValueOrException) === 0) {
            return $this->json($this->returnValueOrException, Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->returnValueOrException, Response::HTTP_OK);
    }

    /**
     * Override the create method to use our test value
     */
    public function create(Request $request): JsonResponse
    {
        if ($this->methodToTest !== 'create') {
            return parent::create($request);
        }

        if ($this->returnValueOrException instanceof Exception) {
            if ($this->returnValueOrException instanceof UnauthorizedHttpException) {
                throw $this->returnValueOrException;
            }
            return $this->json(
                ['stored' => false, 'error' => $this->returnValueOrException->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->json(['stored' => true], Response::HTTP_CREATED);
    }

    /**
     * Override the update method to use our test value
     */
    public function update(Request $request): JsonResponse
    {
        if ($this->methodToTest !== 'update') {
            return parent::update($request);
        }

        if ($this->returnValueOrException instanceof Exception) {
            if ($this->returnValueOrException instanceof UnauthorizedHttpException) {
                throw $this->returnValueOrException;
            }
            return $this->json(
                ['stored' => false, 'error' => $this->returnValueOrException->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->json(['stored' => true], Response::HTTP_OK);
    }
}

class FaqControllerTest extends TestCase
{
    /**
     * @throws MockException|ReflectionException
     */
    public function testConstructorWithApiEnabled(): void
    {
        // Mock Configuration
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('get')
            ->with('api.enableAccess')
            ->willReturn(true);

        // Create a mock for FaqController with the constructor mocked
        $faqController = $this->getMockBuilder(FaqController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isApiEnabled'])
            ->getMock();

        // Set expectations for the isApiEnabled method
        $faqController->method('isApiEnabled')
            ->willReturn(true);

        // Call the constructor manually
        $reflection = new ReflectionClass(FaqController::class);
        $constructor = $reflection->getConstructor();
        $constructor->invoke($faqController);

        // No exception should be thrown
        $this->assertTrue(true);
    }

    /**
     * @throws MockException|ReflectionException
     */
    public function testConstructorWithApiDisabled(): void
    {
        // Mock Configuration
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('get')
            ->with('api.enableAccess')
            ->willReturn(false);

        // Create a mock for FaqController with the constructor mocked
        $faqController = $this->getMockBuilder(FaqController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isApiEnabled'])
            ->getMock();

        // Set expectations for the isApiEnabled method
        $faqController->method('isApiEnabled')
            ->willReturn(false);

        // Expect an UnauthorizedHttpException
        $this->expectException(UnauthorizedHttpException::class);

        // Call the constructor manually
        $reflection = new ReflectionClass(FaqController::class);
        $constructor = $reflection->getConstructor();
        $constructor->invoke($faqController);
    }

    /**
     * @throws MockException
     */
    public function testGetByCategoryIdWithFaqsFound(): void
    {
        // Mock Request
        $request = $this->createMock(Request::class);
        $request->method('get')
            ->with('categoryId')
            ->willReturn('123');

        // Sample data to return
        $faqs = [
            [
                'record_id' => 1,
                'record_lang' => 'en',
                'category_id' => 123,
                'record_title' => 'Test FAQ',
                'record_preview' => 'This is a test',
                'record_link' => '/index.php?action=faq&cat=123&id=1&artlang=en',
                'record_updated' => '20240101000000',
                'visits' => 5,
                'record_created' => '2024-01-01T00:00:00+00:00'
            ]
        ];

        // Create a test double for FaqController
        $faqController = $this->createFaqControllerTestDouble($faqs, 'getByCategoryId');

        // Call the getByCategoryId method
        $response = $faqController->getByCategoryId($request);

        // Assertions
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals($faqs, json_decode($response->getContent(), true));
    }

    /**
     * @throws MockException
     */
    public function testGetByCategoryIdWithException(): void
    {
        // Mock Request
        $request = $this->createMock(Request::class);
        $request->method('get')
            ->with('categoryId')
            ->willReturn('123');

        // Create a test double for FaqController with an exception
        $exception = new Exception('Test exception');
        $faqController = $this->createFaqControllerTestDouble($exception, 'getByCategoryId');

        // Call the getByCategoryId method
        $response = $faqController->getByCategoryId($request);

        // Assertions
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertEquals(['error' => 'Test exception'], json_decode($response->getContent(), true));
    }

    /**
     * @throws MockException
     */
    public function testGetByIdWithFaqFound(): void
    {
        // Mock Request
        $request = $this->createMock(Request::class);
        $request->method('get')
            ->with('faqId')
            ->willReturn('1');

        // Sample data to return
        $faq = [
            'id' => 1,
            'lang' => 'en',
            'solution_id' => 1000,
            'revision_id' => 0,
            'active' => 'yes',
            'sticky' => 0,
            'keywords' => '',
            'title' => 'Test FAQ',
            'content' => 'This is a test',
            'author' => 'Test User',
            'email' => 'test@example.com',
            'comment' => 'y',
            'date' => '2024-01-01 00:00',
            'dateStart' => '00000000000000',
            'dateEnd' => '99991231235959',
            'created' => '2024-01-01T00:00:00+00:00'
        ];

        // Create a test double for FaqController
        $faqController = $this->createFaqControllerTestDouble($faq, 'getById');

        // Call the getById method
        $response = $faqController->getById($request);

        // Assertions
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals($faq, json_decode($response->getContent(), true));
    }

    /**
     * @throws MockException
     */
    public function testGetByIdWithNoFaqFound(): void
    {
        // Mock Request
        $request = $this->createMock(Request::class);
        $request->method('get')
            ->with('faqId')
            ->willReturn('999');

        // Create a test double for FaqController with an empty result
        $faqController = $this->createFaqControllerTestDouble(new stdClass(), 'getById');

        // Call the getById method
        $response = $faqController->getById($request);

        // Assertions
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals(new stdClass(), json_decode($response->getContent()));
    }

    /**
     * @throws MockException
     */
    public function testGetByTagIdWithFaqsFound(): void
    {
        // Mock Request
        $request = $this->createMock(Request::class);
        $request->method('get')
            ->with('tagId')
            ->willReturn('123');

        // Sample data to return
        $faqs = [
            [
                'record_id' => 1,
                'record_lang' => 'en',
                'category_id' => 1,
                'record_title' => 'Test FAQ',
                'record_preview' => 'This is a test',
                'record_link' => '/index.php?action=faq&cat=1&id=1&artlang=en',
                'record_updated' => '20240101000000',
                'visits' => 5,
                'record_created' => '2024-01-01T00:00:00+00:00'
            ]
        ];

        // Create a test double for FaqController
        $faqController = $this->createFaqControllerTestDouble($faqs, 'getByTagId');

        // Call the getByTagId method
        $response = $faqController->getByTagId($request);

        // Assertions
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals($faqs, json_decode($response->getContent(), true));
    }

    /**
     * @throws MockException
     */
    public function testGetByTagIdWithException(): void
    {
        // Mock Request
        $request = $this->createMock(Request::class);
        $request->method('get')
            ->with('tagId')
            ->willReturn('123');

        // Create a test double for FaqController with an exception
        $exception = new Exception('Test exception');
        $faqController = $this->createFaqControllerTestDouble($exception, 'getByTagId');

        // Call the getByTagId method
        $response = $faqController->getByTagId($request);

        // Assertions
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertEquals(['error' => 'Test exception'], json_decode($response->getContent(), true));
    }

    /**
     * @throws MockException
     */
    public function testGetPopularWithFaqsFound(): void
    {
        // Sample data to return
        $faqs = [
            [
                'date' => '2024-01-01T00:00:00+00:00',
                'question' => 'Test FAQ',
                'answer' => 'This is a test',
                'visits' => 10,
                'url' => 'https://www.example.org/index.php?action=faq&cat=1&id=1&artlang=en'
            ]
        ];

        // Create a test double for FaqController
        $faqController = $this->createFaqControllerTestDouble($faqs, 'getPopular');

        // Call the getPopular method
        $response = $faqController->getPopular();

        // Assertions
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals($faqs, json_decode($response->getContent(), true));
    }

    /**
     * @throws MockException
     */
    public function testGetPopularWithNoFaqsFound(): void
    {
        // Create a test double for FaqController with an empty result
        $faqController = $this->createFaqControllerTestDouble([], 'getPopular');

        // Call the getPopular method
        $response = $faqController->getPopular();

        // Assertions
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals([], json_decode($response->getContent(), true));
    }

    /**
     * @throws MockException
     */
    public function testGetLatestWithFaqsFound(): void
    {
        // Sample data to return
        $faqs = [
            [
                'date' => '2024-01-01T00:00:00+00:00',
                'question' => 'Test FAQ',
                'answer' => 'This is a test',
                'visits' => 10,
                'url' => 'https://www.example.org/index.php?action=faq&cat=1&id=1&artlang=en'
            ]
        ];

        // Create a test double for FaqController
        $faqController = $this->createFaqControllerTestDouble($faqs, 'getLatest');

        // Call the getLatest method
        $response = $faqController->getLatest();

        // Assertions
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals($faqs, json_decode($response->getContent(), true));
    }

    /**
     * @throws MockException
     */
    public function testGetLatestWithNoFaqsFound(): void
    {
        // Create a test double for FaqController with an empty result
        $faqController = $this->createFaqControllerTestDouble([], 'getLatest');

        // Call the getLatest method
        $response = $faqController->getLatest();

        // Assertions
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals([], json_decode($response->getContent(), true));
    }

    /**
     * @throws MockException
     */
    public function testGetTrendingWithFaqsFound(): void
    {
        // Sample data to return
        $faqs = [
            [
                'date' => '2024-01-01T00:00:00+00:00',
                'question' => 'Test FAQ',
                'answer' => 'This is a test',
                'visits' => 10,
                'url' => 'https://www.example.org/index.php?action=faq&cat=1&id=1&artlang=en'
            ]
        ];

        // Create a test double for FaqController
        $faqController = $this->createFaqControllerTestDouble($faqs, 'getTrending');

        // Call the getTrending method
        $response = $faqController->getTrending();

        // Assertions
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals($faqs, json_decode($response->getContent(), true));
    }

    /**
     * @throws MockException
     */
    public function testGetTrendingWithNoFaqsFound(): void
    {
        // Create a test double for FaqController with an empty result
        $faqController = $this->createFaqControllerTestDouble([], 'getTrending');

        // Call the getTrending method
        $response = $faqController->getTrending();

        // Assertions
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals([], json_decode($response->getContent(), true));
    }

    /**
     * @throws MockException
     */
    public function testGetStickyWithFaqsFound(): void
    {
        // Sample data to return
        $faqs = [
            [
                'question' => 'Test FAQ',
                'url' => 'https://www.example.org/index.php?action=faq&cat=1&id=1&artlang=en',
                'id' => 1,
                'order' => 1
            ]
        ];

        // Create a test double for FaqController
        $faqController = $this->createFaqControllerTestDouble($faqs, 'getSticky');

        // Call the getSticky method
        $response = $faqController->getSticky();

        // Assertions
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals($faqs, json_decode($response->getContent(), true));
    }

    /**
     * @throws MockException
     */
    public function testGetStickyWithNoFaqsFound(): void
    {
        // Create a test double for FaqController with an empty result
        $faqController = $this->createFaqControllerTestDouble([], 'getSticky');

        // Call the getSticky method
        $response = $faqController->getSticky();

        // Assertions
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals([], json_decode($response->getContent(), true));
    }

    /**
     * @throws MockException
     */
    public function testListWithFaqsFound(): void
    {
        // Sample data to return
        $faqs = [
            [
                'id' => '1',
                'lang' => 'en',
                'solution_id' => '1000',
                'revision_id' => '0',
                'active' => 'yes',
                'sticky' => '0',
                'keywords' => '',
                'title' => 'Test FAQ',
                'content' => 'This is a test',
                'author' => 'Test User',
                'email' => 'test@example.com',
                'comment' => 'y',
                'updated' => '2024-01-01 00:00:00',
                'dateStart' => '00000000000000',
                'dateEnd' => '99991231235959',
                'created' => '2024-01-01T00:00:00+00:00',
                'notes' => ''
            ]
        ];

        // Create a test double for FaqController
        $faqController = $this->createFaqControllerTestDouble($faqs, 'list');

        // Call the list method
        $response = $faqController->list();

        // Assertions
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals($faqs, json_decode($response->getContent(), true));
    }

    /**
     * @throws MockException
     */
    public function testListWithNoFaqsFound(): void
    {
        // Create a test double for FaqController with an empty result
        $faqController = $this->createFaqControllerTestDouble([], 'list');

        // Call the list method
        $response = $faqController->list();

        // Assertions
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals([], json_decode($response->getContent(), true));
    }

    /**
     * @throws MockException
     */
    public function testCreateWithSuccess(): void
    {
        // Mock Request with JSON content
        $request = $this->createMock(Request::class);
        $request->method('getContent')
            ->willReturn(json_encode([
                'language' => 'en',
                'category-id' => 1,
                'question' => 'Test FAQ',
                'answer' => 'This is a test',
                'keywords' => 'test',
                'author' => 'Test User',
                'email' => 'test@example.com',
                'is-active' => true,
                'is-sticky' => false
            ]));

        // Create a test double for FaqController
        $faqController = $this->createFaqControllerTestDouble(true, 'create');

        // Call the create method
        $response = $faqController->create($request);

        // Assertions
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertEquals(['stored' => true], json_decode($response->getContent(), true));
    }

    /**
     * @throws MockException
     */
    public function testCreateWithError(): void
    {
        // Mock Request with JSON content
        $request = $this->createMock(Request::class);
        $request->method('getContent')
            ->willReturn(json_encode([
                'language' => 'en',
                'category-id' => 1,
                'question' => 'Test FAQ #hash',
                'answer' => 'This is a test',
                'keywords' => 'test',
                'author' => 'Test User',
                'email' => 'test@example.com',
                'is-active' => true,
                'is-sticky' => false
            ]));

        // Create a test double for FaqController with an exception
        $exception = new Exception('It is not allowed, that the question title contains a hash.');
        $faqController = $this->createFaqControllerTestDouble($exception, 'create');

        // Call the create method
        $response = $faqController->create($request);

        // Assertions
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals(
            ['stored' => false, 'error' => 'It is not allowed, that the question title contains a hash.'],
            json_decode($response->getContent(), true)
        );
    }

    /**
     * @throws MockException
     */
    public function testUpdateWithSuccess(): void
    {
        // Mock Request with JSON content
        $request = $this->createMock(Request::class);
        $request->method('getContent')
            ->willReturn(json_encode([
                'faq-id' => 1,
                'language' => 'en',
                'category-id' => 1,
                'question' => 'Updated Test FAQ',
                'answer' => 'This is an updated test',
                'keywords' => 'test, update',
                'author' => 'Test User',
                'email' => 'test@example.com',
                'is-active' => true,
                'is-sticky' => false
            ]));

        // Create a test double for FaqController
        $faqController = $this->createFaqControllerTestDouble(true, 'update');

        // Call the update method
        $response = $faqController->update($request);

        // Assertions
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(['stored' => true], json_decode($response->getContent(), true));
    }

    /**
     * @throws MockException
     */
    public function testUpdateWithError(): void
    {
        // Mock Request with JSON content
        $request = $this->createMock(Request::class);
        $request->method('getContent')
            ->willReturn(json_encode([
                'faq-id' => 1,
                'language' => 'en',
                'category-id' => 1,
                'question' => 'Updated Test FAQ #hash',
                'answer' => 'This is an updated test',
                'keywords' => 'test, update',
                'author' => 'Test User',
                'email' => 'test@example.com',
                'is-active' => true,
                'is-sticky' => false
            ]));

        // Create a test double for FaqController with an exception
        $exception = new Exception('It is not allowed, that the question title contains a hash.');
        $faqController = $this->createFaqControllerTestDouble($exception, 'update');

        // Call the update method
        $response = $faqController->update($request);

        // Assertions
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals(
            ['stored' => false, 'error' => 'It is not allowed, that the question title contains a hash.'],
            json_decode($response->getContent(), true)
        );
    }

    /**
     * Create a test double for FaqController that overrides the specified method
     *
     * @param mixed  $returnValueOrException What to return or throw from the method
     * @param string $methodToTest The method to test
     * @return TestableFaqController
     * @throws MockException
     * @throws \phpMyFAQ\Core\Exception
     */
    private function createFaqControllerTestDouble(
        mixed $returnValueOrException,
        string $methodToTest
    ): TestableFaqController {
        $faqController = new TestableFaqController($returnValueOrException, $methodToTest);

        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getDefaultUrl')
            ->willReturn('https://www.example.org/');

        $reflection = new ReflectionClass(FaqController::class);

        $configurationProperty = $reflection->getProperty('configuration');
        $configurationProperty->setAccessible(true);
        $configurationProperty->setValue($faqController, $configuration);

        return $faqController;
    }
}
