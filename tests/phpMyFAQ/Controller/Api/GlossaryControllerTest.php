<?php

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Language;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class GlossaryControllerTest extends TestCase
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

    public function testListReturnsGlossaryItems(): void
    {
        $glossaryController = new GlossaryController();

        $request = Request::create(
            '/api/v3.2/glossary',
            'GET',
            [],
            [],
            [],
            [
                'HTTP_ACCEPT_LANGUAGE' => 'en',
            ],
        );

        $response = $glossaryController->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
    }

    public function testListHandlesAcceptLanguageHeader(): void
    {
        $glossaryController = new GlossaryController();

        $request = Request::create(
            '/api/v3.2/glossary',
            'GET',
            [],
            [],
            [],
            [
                'HTTP_ACCEPT_LANGUAGE' => 'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
            ],
        );

        $response = $glossaryController->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }

    public function testListWithoutAcceptLanguageHeader(): void
    {
        $glossaryController = new GlossaryController();

        $request = Request::create('/api/v3.2/glossary', 'GET');

        $response = $glossaryController->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }

    public function testListReturnsJsonData(): void
    {
        $glossaryController = new GlossaryController();

        $request = Request::create('/api/v3.2/glossary', 'GET');

        $response = $glossaryController->list($request);

        $this->assertJson($response->getContent());
    }

    public function testListResponseContentIsNotNull(): void
    {
        $glossaryController = new GlossaryController();

        $request = Request::create('/api/v3.2/glossary', 'GET');

        $response = $glossaryController->list($request);

        $this->assertNotNull($response->getContent());
    }

    public function testListReturnsEmptyArrayOn404(): void
    {
        $glossaryController = new GlossaryController();

        $request = Request::create('/api/v3.2/glossary', 'GET');

        $response = $glossaryController->list($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue($data['success']);

        // Data can be empty array if no glossary items exist
        $this->assertIsArray($data['data']);
    }
}
