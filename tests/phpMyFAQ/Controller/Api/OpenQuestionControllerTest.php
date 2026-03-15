<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use Exception;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Question;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(OpenQuestionController::class)]
#[UsesNamespace('phpMyFAQ')]
class OpenQuestionControllerTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configuration = $this->createConfiguration();
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

    /**
     * @throws Exception
     */
    public function testListReturnsJsonResponse(): void
    {
        $question = $this->createStub(Question::class);
        $question->method('getAll')->willReturn([]);
        $controller = new OpenQuestionController($question);
        $response = $controller->list();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testListReturnsValidStatusCode(): void
    {
        $controller = new OpenQuestionController($this->createStub(Question::class));
        $response = $controller->list();

        $this->assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);
    }

    /**
     * @throws Exception
     */
    public function testListReturnsJsonData(): void
    {
        $controller = new OpenQuestionController($this->createStub(Question::class));
        $response = $controller->list();

        $this->assertJson($response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testListReturnsArrayData(): void
    {
        $controller = new OpenQuestionController($this->createStub(Question::class));
        $response = $controller->list();

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    /**
     * @throws Exception
     */
    public function testListResponseContentIsNotNull(): void
    {
        $controller = new OpenQuestionController($this->createStub(Question::class));
        $response = $controller->list();

        $this->assertNotNull($response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testListReturnsEmptyArrayOn404(): void
    {
        $question = $this->createStub(Question::class);
        $question->method('getAll')->willReturn([]);
        $controller = new OpenQuestionController($question);
        $response = $controller->list();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue($data['success']);

        // Data can be empty array if no open questions exist
        $this->assertIsArray($data['data']);
    }

    /**
     * @throws Exception
     */
    public function testListAppliesSortingPaginationAndOnlyPublicFlag(): void
    {
        $question = $this->createMock(Question::class);
        $question
            ->expects($this->once())
            ->method('getAll')
            ->with(true)
            ->willReturn([
                ['id' => 1, 'username' => 'Alice', 'created' => '20240101', 'categoryId' => 5],
                ['id' => 3, 'username' => 'Charlie', 'created' => '20240103', 'categoryId' => 3],
                ['id' => 2, 'username' => 'Bob', 'created' => '20240102', 'categoryId' => 4],
            ]);

        $controller = new OpenQuestionController($question);
        $this->configuration->set('api.onlyPublicQuestions', true);

        $request = Request::create('/api/v3.2/open-questions', 'GET', [
            'limit' => 1,
            'offset' => 1,
            'sort' => 'id',
            'order' => 'desc',
        ]);

        $response = $controller->list($request);

        $data = json_decode((string) $response->getContent(), true);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertCount(1, $data['data']);
        $this->assertSame(2, $data['data'][0]['id']);
        $this->assertSame('id', $data['meta']['sorting']['field']);
        $this->assertSame('desc', $data['meta']['sorting']['order']);
        $this->assertSame(3, $data['meta']['pagination']['total']);
    }
}
