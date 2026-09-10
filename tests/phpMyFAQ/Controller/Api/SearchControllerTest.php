<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Search;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(SearchController::class)]
#[UsesNamespace('phpMyFAQ')]
class SearchControllerTest extends TestCase
{
    private Configuration $configuration;
    private Sqlite3 $dbHandle;
    private string $databasePath;
    private ?Configuration $previousConfiguration = null;

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

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();
        $configurationProperty->setValue(null, null);

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-api-search-controller-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->configuration = new Configuration($this->dbHandle);

        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $this->dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');
        Database::setTablePrefix('');

        $language = new Language($this->configuration, new Session(new MockArraySessionStorage()));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);
    }

    protected function tearDown(): void
    {
        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        $this->dbHandle->close();
        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, null);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, '');
        @unlink($this->databasePath);

        parent::tearDown();
    }

    public function testSearchReturnsJsonResponse(): void
    {
        $request = new Request(['q' => 'test']);
        $controller = new SearchController($this->createStub(Search::class));
        $response = $controller->search($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testSearchReturnsValidStatusCode(): void
    {
        $request = new Request(['q' => 'test']);
        $controller = new SearchController($this->createStub(Search::class));
        $response = $controller->search($request);

        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND,
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ]);
    }

    public function testPopularReturnsJsonResponse(): void
    {
        $controller = new SearchController($this->createStub(Search::class));
        $response = $controller->popular();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testPopularReturnsValidStatusCode(): void
    {
        $controller = new SearchController($this->createStub(Search::class));
        $response = $controller->popular();

        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }

    public function testSearchWithEmptyQuery(): void
    {
        $request = new Request(['q' => '']);
        $controller = new SearchController($this->createStub(Search::class));
        $response = $controller->search($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testSearchReturnsJsonData(): void
    {
        $request = new Request(['q' => 'test']);
        $controller = new SearchController($this->createStub(Search::class));
        $response = $controller->search($request);

        $this->assertJson($response->getContent());
    }

    public function testSearchReturnsArrayData(): void
    {
        $request = new Request(['q' => 'test']);
        $controller = new SearchController($this->createStub(Search::class));
        $response = $controller->search($request);

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testPopularReturnsJsonData(): void
    {
        $controller = new SearchController($this->createStub(Search::class));
        $response = $controller->popular();

        $this->assertJson($response->getContent());
    }

    public function testPopularReturnsArrayData(): void
    {
        $controller = new SearchController($this->createStub(Search::class));
        $response = $controller->popular();

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testSearchWithSpecialCharacters(): void
    {
        $request = new Request(['q' => '@#$%^&*()']);
        $controller = new SearchController($this->createStub(Search::class));
        $response = $controller->search($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testSearchWithUnicodeCharacters(): void
    {
        $request = new Request(['q' => '日本語テスト']);
        $controller = new SearchController($this->createStub(Search::class));
        $response = $controller->search($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertJson($response->getContent());
    }

    public function testSearchWithLongQuery(): void
    {
        $request = new Request(['q' => str_repeat('a', 1000)]);
        $controller = new SearchController($this->createStub(Search::class));
        $response = $controller->search($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testSearchWithWhitespaceQuery(): void
    {
        $request = new Request(['q' => '   ']);
        $controller = new SearchController($this->createStub(Search::class));
        $response = $controller->search($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testSearchResponseContentIsNotNull(): void
    {
        $request = new Request(['q' => 'test']);
        $controller = new SearchController($this->createStub(Search::class));
        $response = $controller->search($request);

        $this->assertNotNull($response->getContent());
    }

    public function testPopularResponseContentIsNotNull(): void
    {
        $controller = new SearchController($this->createStub(Search::class));
        $response = $controller->popular();

        $this->assertNotNull($response->getContent());
    }

    public function testPopularReturnsNotFoundWhenNoPopularSearchesExist(): void
    {
        $search = $this->createMock(Search::class);
        $search->expects($this->once())->method('getMostPopularSearches')->willReturn([]);

        $controller = new SearchController($search);
        $response = $controller->popular();

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame('[]', (string) $response->getContent());
    }

    public function testPopularReturnsOkWhenPopularSearchesExist(): void
    {
        $search = $this->createMock(Search::class);
        $search
            ->expects($this->once())
            ->method('getMostPopularSearches')
            ->willReturn([
                ['searchterm' => 'test', 'number' => 3, 'lang' => 'en'],
            ]);

        $controller = new SearchController($search);
        $response = $controller->popular();
        $payload = json_decode((string) $response->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertIsArray($payload);
        $this->assertSame('test', $payload[0]['searchterm']);
    }

    public function testSearchReturnsPaginatedDataForReviewedResultSet(): void
    {
        $result = new stdClass();
        $result->id = 0;
        $result->lang = 'en';
        $result->category_id = 1;
        $result->question = 'How to test search?';
        $result->answer = '<p>This is a longer answer for testing.</p>';

        $search = $this->createMock(Search::class);
        $search->expects($this->once())->method('setCategory');
        $search->expects($this->once())->method('search')->willReturn([$result]);

        $request = new Request(['q' => 'test', 'page' => 1, 'per_page' => 10]);
        $controller = new SearchController($search);
        $response = $controller->search($request);
        $payload = json_decode((string) $response->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertCount(1, $payload['data']);
        $this->assertStringContainsString('/faq/1/0/en/', $payload['data'][0]['link']);
        $this->assertSame(1, $payload['meta']['pagination']['total']);
    }

    public function testSearchShapesOnlyTheRequestedPageOfHits(): void
    {
        $hits = [];
        for ($id = 1; $id <= 30; ++$id) {
            // Grant the anonymous user and group access, whichever permission level the test database uses.
            $this->dbHandle->query(sprintf('INSERT INTO faqdata_user (record_id, user_id) VALUES (%d, -1)', $id));
            $this->dbHandle->query(sprintf('INSERT INTO faqdata_group (record_id, group_id) VALUES (%d, -1)', $id));

            $hit = new stdClass();
            $hit->id = $id;
            $hit->lang = 'en';
            $hit->category_id = 1;
            $hit->question = 'Question ' . $id;
            $hit->answer = '<p>Answer ' . $id . '</p>';
            $hits[] = $hit;
        }

        $search = $this->createMock(Search::class);
        $search->method('search')->willReturn($hits);

        $request = new Request(['q' => 'question', 'page' => 2, 'per_page' => 10]);
        $response = (new SearchController($search))->search($request);
        $payload = json_decode((string) $response->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(range(11, 20), array_column($payload['data'], 'id'));
        $this->assertSame('Answer 11', $payload['data'][0]['answer']);
        $this->assertStringContainsString('/faq/1/11/en/question-11', $payload['data'][0]['link']);
        $this->assertSame(30, $payload['meta']['pagination']['total']);

        // Hits outside the requested page are left untouched: no tag stripping, no link building.
        $this->assertSame('<p>Answer 1</p>', $hits[0]->answer);
        $this->assertFalse(property_exists($hits[0], 'link'));
        $this->assertSame('<p>Answer 30</p>', $hits[29]->answer);
    }
}
