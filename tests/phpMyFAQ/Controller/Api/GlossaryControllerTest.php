<?php

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Glossary;
use phpMyFAQ\Language;
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
#[CoversClass(GlossaryController::class)]
#[UsesNamespace('phpMyFAQ')]
class GlossaryControllerTest extends TestCase
{
    private Configuration $configuration;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->configuration = $this->createConfiguration();
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');
        $this->configuration->setLanguage($language);
    }

    private function createConfiguration(): Configuration
    {
        try {
            return Configuration::getConfigurationInstance();
        } catch (\TypeError) {
            $db = new Sqlite3();
            $db->connect(PMF_TEST_DIR . '/test.db', '', '');
            $configuration = new Configuration($db);

            $configurationReflection = new \ReflectionClass(Configuration::class);
            $configurationProperty = $configurationReflection->getProperty('configuration');
            $configurationProperty->setValue(null, $configuration);

            return $configuration;
        }
    }

    public function testListReturnsGlossaryItems(): void
    {
        $glossary = $this->createStub(Glossary::class);
        $glossary
            ->method('fetchAll')
            ->willReturn([
                ['id' => 2, 'item' => 'Zulu', 'definition' => 'Last'],
                ['id' => 1, 'item' => 'Alpha', 'definition' => 'First'],
            ]);
        $language = $this->createStub(Language::class);
        $language->method('setLanguageByAcceptLanguage')->willReturn('en');

        $glossaryController = new GlossaryController($glossary, $language);

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
        $this->assertTrue($content['success']);
        $this->assertSame('Alpha', $content['data'][0]['item']);
        $this->assertSame(2, $content['meta']['pagination']['total']);
        $this->assertSame('item', $content['meta']['sorting']['field']);
        $this->assertSame('asc', $content['meta']['sorting']['order']);
    }

    public function testListHandlesAcceptLanguageHeader(): void
    {
        $glossaryController = new GlossaryController(
            $this->createStub(Glossary::class),
            $this->createStub(Language::class),
        );

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
        $glossaryController = new GlossaryController(
            $this->createStub(Glossary::class),
            $this->createStub(Language::class),
        );

        $request = Request::create('/api/v3.2/glossary', 'GET');

        $response = $glossaryController->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }

    public function testListReturnsJsonData(): void
    {
        $glossaryController = new GlossaryController(
            $this->createStub(Glossary::class),
            $this->createStub(Language::class),
        );

        $request = Request::create('/api/v3.2/glossary', 'GET');

        $response = $glossaryController->list($request);

        $this->assertJson($response->getContent());
    }

    public function testListResponseContentIsNotNull(): void
    {
        $glossaryController = new GlossaryController(
            $this->createStub(Glossary::class),
            $this->createStub(Language::class),
        );

        $request = Request::create('/api/v3.2/glossary', 'GET');

        $response = $glossaryController->list($request);

        $this->assertNotNull($response->getContent());
    }

    public function testListReturnsEmptyArrayOn404(): void
    {
        $glossary = $this->createStub(Glossary::class);
        $glossary->method('fetchAll')->willReturn([]);
        $language = $this->createStub(Language::class);
        $language->method('setLanguageByAcceptLanguage')->willReturn('');

        $glossaryController = new GlossaryController($glossary, $language);

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

    public function testListAppliesDescendingPaginationSorting(): void
    {
        $glossary = $this->createMock(Glossary::class);
        $glossary->expects($this->once())->method('setLanguage')->with('de');
        $glossary
            ->method('fetchAll')
            ->willReturn([
                ['id' => 1, 'item' => 'Alpha', 'definition' => 'One'],
                ['id' => 3, 'item' => 'Gamma', 'definition' => 'Three'],
                ['id' => 2, 'item' => 'Beta', 'definition' => 'Two'],
            ]);

        $language = $this->createStub(Language::class);
        $language->method('setLanguageByAcceptLanguage')->willReturn('de');

        $glossaryController = new GlossaryController($glossary, $language);

        $request = Request::create('/api/v3.2/glossary', 'GET', [
            'limit' => 1,
            'offset' => 1,
            'sort' => 'id',
            'order' => 'desc',
        ]);

        $response = $glossaryController->list($request);

        $data = json_decode((string) $response->getContent(), true);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertCount(1, $data['data']);
        $this->assertSame(2, $data['data'][0]['id']);
        $this->assertSame('id', $data['meta']['sorting']['field']);
        $this->assertSame('desc', $data['meta']['sorting']['order']);
    }
}
