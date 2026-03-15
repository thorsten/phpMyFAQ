<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Faq\Permission;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Search;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(AutoCompleteController::class)]
#[UsesNamespace('phpMyFAQ')]
class AutoCompleteControllerTest extends TestCase
{
    private Configuration $configuration;
    private Permission $faqPermission;
    private Search $faqSearch;
    private SearchHelper $faqSearchHelper;
    private Plurals $plurals;

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

        $this->faqPermission = $this->createStub(Permission::class);
        $this->faqSearch = $this->createStub(Search::class);
        $this->faqSearchHelper = $this->createStub(SearchHelper::class);
        $this->plurals = $this->createStub(Plurals::class);
    }

    private function createController(): AutoCompleteController
    {
        return new AutoCompleteController(
            $this->faqPermission,
            $this->faqSearch,
            $this->faqSearchHelper,
            $this->plurals,
        );
    }

    /**
     * @throws \Exception
     */
    public function testSearchReturnsJsonResponse(): void
    {
        $request = new Request(['search' => 'test']);

        $controller = $this->createController();
        $response = $controller->search($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws \Exception
     */
    public function testSearchWithValidQueryReturnsOkOrNotFound(): void
    {
        $request = new Request(['search' => 'test']);

        $controller = $this->createController();
        $response = $controller->search($request);

        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }

    /**
     * @throws \Exception
     */
    public function testSearchWithoutQueryReturnsNotFound(): void
    {
        $request = new Request();

        $controller = $this->createController();
        $response = $controller->search($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testSearchReturnsValidJsonContent(): void
    {
        $request = new Request(['search' => 'test']);

        $controller = $this->createController();
        $response = $controller->search($request);

        $this->assertJson($response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testSearchReturnsArrayData(): void
    {
        $request = new Request(['search' => 'test']);

        $controller = $this->createController();
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

        $controller = $this->createController();
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

        $controller = $this->createController();
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
            $controller = $this->createController();
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

        $controller = $this->createController();
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

        $controller = $this->createController();
        $response = $controller->search($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }
}
