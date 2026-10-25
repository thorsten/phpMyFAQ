<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Language;
use phpMyFAQ\Tags;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(TagController::class)]
#[UsesNamespace('phpMyFAQ')]
class TagControllerTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->configuration = $this->createTestConfiguration();
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');
        $this->configuration->setLanguage($language);
    }

    public function testListReturnsJsonResponse(): void
    {
        $controller = new TagController($this->createStub(Tags::class));
        $response = $controller->list();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testListReturnsValidStatusCode(): void
    {
        $controller = new TagController($this->createStub(Tags::class));
        $response = $controller->list();

        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }

    public function testListReturnsJsonData(): void
    {
        $controller = new TagController($this->createStub(Tags::class));
        $response = $controller->list();

        $this->assertJson($response->getContent());
    }

    public function testListReturnsArrayData(): void
    {
        $controller = new TagController($this->createStub(Tags::class));
        $response = $controller->list();

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testListResponseContentIsNotNull(): void
    {
        $controller = new TagController($this->createStub(Tags::class));
        $response = $controller->list();

        $this->assertNotNull($response->getContent());
    }

    public function testListReturnsCorrectStructureWhenTagsExist(): void
    {
        $controller = new TagController($this->createStub(Tags::class));
        $response = $controller->list();

        $data = json_decode($response->getContent(), true);

        // Check for paginated response structure
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue($data['success']);

        if ($response->getStatusCode() === Response::HTTP_OK && !empty($data['data'])) {
            // Check pagination metadata exists
            $this->assertArrayHasKey('meta', $data);
            $this->assertArrayHasKey('pagination', $data['meta']);
            $this->assertArrayHasKey('sorting', $data['meta']);

            // Check tag structure
            foreach ($data['data'] as $tag) {
                $this->assertArrayHasKey('tagId', $tag);
                $this->assertArrayHasKey('tagName', $tag);
                $this->assertArrayHasKey('tagFrequency', $tag);
            }
        }
    }

    public function testListReturnsEmptyArrayOn404(): void
    {
        $controller = new TagController($this->createStub(Tags::class));
        $response = $controller->list();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue($data['success']);

        // Data can be empty array if no tags exist
        $this->assertIsArray($data['data']);
    }

    public function testListSortsTagsByRequestedFieldAndOrder(): void
    {
        $tags = $this->createStub(Tags::class);
        $tags->method('getPopularTagsAsArray')->willReturn([
            ['tagId' => 1, 'tagName' => 'php', 'tagFrequency' => 5],
            ['tagId' => 2, 'tagName' => 'apache', 'tagFrequency' => 9],
            ['tagId' => 3, 'tagName' => 'mysql', 'tagFrequency' => 1],
        ]);
        $controller = new TagController($tags);

        $ascending = $controller->list(
            Request::create('/api/v4.0/tags', 'GET', ['sort' => 'tagName', 'order' => 'asc']),
        );
        $ascendingData = json_decode((string) $ascending->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_OK, $ascending->getStatusCode());
        $this->assertSame(['apache', 'mysql', 'php'], array_column($ascendingData['data'], 'tagName'));
        $this->assertSame(['field' => 'tagName', 'order' => 'asc'], $ascendingData['meta']['sorting']);

        $descending = $controller->list(Request::create('/api/v4.0/tags', 'GET', ['sort' => 'tagFrequency']));
        $descendingData = json_decode((string) $descending->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame([9, 5, 1], array_column($descendingData['data'], 'tagFrequency'));
    }
}
