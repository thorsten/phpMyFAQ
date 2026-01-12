<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
class AutoCompleteControllerTest extends TestCase
{
    private Configuration $configuration;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->configuration = Configuration::getConfigurationInstance();
    }

    /**
     * @throws \Exception
     */
    public function testSearchReturnsJsonResponse(): void
    {
        $request = new Request(['search' => 'test']);

        $controller = new AutoCompleteController();
        $response = $controller->search($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws \Exception
     */
    public function testSearchWithValidQueryReturnsOkOrNotFound(): void
    {
        $request = new Request(['search' => 'test']);

        $controller = new AutoCompleteController();
        $response = $controller->search($request);

        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }

    /**
     * @throws \Exception
     */
    public function testSearchWithoutQueryReturnsNotFound(): void
    {
        $request = new Request();

        $controller = new AutoCompleteController();
        $response = $controller->search($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testSearchReturnsValidJsonContent(): void
    {
        $request = new Request(['search' => 'test']);

        $controller = new AutoCompleteController();
        $response = $controller->search($request);

        $this->assertJson($response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testSearchReturnsArrayData(): void
    {
        $request = new Request(['search' => 'test']);

        $controller = new AutoCompleteController();
        $response = $controller->search($request);

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    /**
     * @throws \Exception
     */
    public function testSearchResponseHasCorrectContentType(): void
    {
        $request = new Request(['search' => 'test']);

        $controller = new AutoCompleteController();
        $response = $controller->search($request);

        $this->assertTrue($response->headers->has('Content-Type'));
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }

    /**
     * @throws \Exception
     */
    public function testSearchWithEmptyStringReturnsNotFound(): void
    {
        $request = new Request(['search' => '']);

        $controller = new AutoCompleteController();
        $response = $controller->search($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testSearchWithMultipleQueries(): void
    {
        $queries = ['test', 'faq', 'question', 'help'];

        foreach ($queries as $query) {
            $request = new Request(['search' => $query]);
            $controller = new AutoCompleteController();
            $response = $controller->search($request);

            $this->assertInstanceOf(JsonResponse::class, $response);
            $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
        }
    }

    /**
     * @throws \Exception
     */
    public function testSearchResponseIsNotEmpty(): void
    {
        $request = new Request(['search' => 'test']);

        $controller = new AutoCompleteController();
        $response = $controller->search($request);

        $content = $response->getContent();
        $this->assertNotEmpty($content);
        $this->assertIsString($content);
    }

    /**
     * @throws \Exception
     */
    public function testSearchWithSpecialCharacters(): void
    {
        $request = new Request(['search' => '<script>alert("xss")</script>']);

        $controller = new AutoCompleteController();
        $response = $controller->search($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }
}
