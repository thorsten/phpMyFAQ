<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Language;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class TagControllerTest extends TestCase
{
    private Configuration $configuration;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->configuration = Configuration::getConfigurationInstance();
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');
        $this->configuration->setLanguage($language);
    }

    public function testListReturnsJsonResponse(): void
    {
        $controller = new TagController();
        $response = $controller->list();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testListReturnsValidStatusCode(): void
    {
        $controller = new TagController();
        $response = $controller->list();

        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }

    public function testListReturnsJsonData(): void
    {
        $controller = new TagController();
        $response = $controller->list();

        $this->assertJson($response->getContent());
    }

    public function testListReturnsArrayData(): void
    {
        $controller = new TagController();
        $response = $controller->list();

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testListResponseContentIsNotNull(): void
    {
        $controller = new TagController();
        $response = $controller->list();

        $this->assertNotNull($response->getContent());
    }

    public function testListReturnsCorrectStructureWhenTagsExist(): void
    {
        $controller = new TagController();
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
        $controller = new TagController();
        $response = $controller->list();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue($data['success']);

        // Data can be empty array if no tags exist
        $this->assertIsArray($data['data']);
    }
}
