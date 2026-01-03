<?php

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
class GlossaryControllerTest extends TestCase
{
    private Configuration $configuration;

    public function testListReturnsGlossaryItems(): void
    {
        $this->configuration = Configuration::getConfigurationInstance();

        $glossaryController = new GlossaryController();

        $request = Request::create('/api/v3.2/glossary', 'GET', [], [], [], [
            'HTTP_ACCEPT_LANGUAGE' => 'en',
        ]);

        $response = $glossaryController->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        // Response can be 200 with data or 404 if no glossary items exist
        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
    }

    public function testListHandlesAcceptLanguageHeader(): void
    {
        $this->configuration = Configuration::getConfigurationInstance();

        $glossaryController = new GlossaryController();

        // Test with complex Accept-Language header
        $request = Request::create('/api/v3.2/glossary', 'GET', [], [], [], [
            'HTTP_ACCEPT_LANGUAGE' => 'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
        ]);

        $response = $glossaryController->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }

    public function testListWithoutAcceptLanguageHeader(): void
    {
        $this->configuration = Configuration::getConfigurationInstance();

        $glossaryController = new GlossaryController();

        $request = Request::create('/api/v3.2/glossary', 'GET');

        $response = $glossaryController->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }
}
