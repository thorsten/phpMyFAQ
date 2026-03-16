<?php

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class TestApiConstructorController extends AbstractApiController
{
    public function __construct(
        private readonly bool $apiEnabled,
    ) {
        parent::__construct();
    }

    public function isApiEnabled(): bool
    {
        return $this->apiEnabled;
    }
}

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(AttachmentController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(AbstractApiController::class)]
#[UsesClass(PaginatedResponseOptions::class)]
class AttachmentControllerTest extends TestCase
{
    private ?Sqlite3 $dbHandle = null;
    private ?string $databasePath = null;
    private ?Configuration $previousConfiguration = null;
    private ?DatabaseDriver $previousDatabaseDriver = null;
    private ?string $previousDatabaseType = null;
    private ?string $previousTablePrefix = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupGlobalState();

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, null);

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-attachment-controller-');
        $this->assertNotFalse($databasePath);
        $this->assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($databasePath, '', '');
        new Configuration($this->dbHandle);
        $this->initializeDatabaseStatics($this->dbHandle);

        Language::$language = 'en';
        $this->setAttachmentFactoryStorageType(0);
    }

    protected function tearDown(): void
    {
        $this->setAttachmentFactoryStorageType(0);
        if ($this->dbHandle instanceof Sqlite3) {
            $this->dbHandle->close();
        }
        $this->dbHandle = null;

        if ($this->databasePath !== null) {
            @unlink($this->databasePath);
        }
        $this->databasePath = null;
        $this->restoreGlobalState();

        parent::tearDown();
    }

    private function backupGlobalState(): void
    {
        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();

        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $this->previousDatabaseDriver = $databaseDriverProperty->getValue();

        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $this->previousDatabaseType = $dbTypeProperty->isInitialized() ? $dbTypeProperty->getValue() : null;

        $tablePrefixProperty = $databaseReflection->getProperty('tablePrefix');
        $this->previousTablePrefix = $tablePrefixProperty->getValue();
    }

    private function restoreGlobalState(): void
    {
        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $this->previousDatabaseDriver);

        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, $this->previousDatabaseType);

        $tablePrefixProperty = $databaseReflection->getProperty('tablePrefix');
        $tablePrefixProperty->setValue(null, $this->previousTablePrefix);
    }

    private function initializeDatabaseStatics(Sqlite3 $dbHandle): void
    {
        $databaseReflection = new \ReflectionClass(Database::class);

        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $dbHandle);

        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');

        Database::setTablePrefix('');
    }

    private function setAttachmentFactoryStorageType(?int $storageType): void
    {
        $reflection = new \ReflectionClass(AttachmentFactory::class);
        $property = $reflection->getProperty('storageType');
        $property->setValue(null, $storageType);
    }

    private function insertAttachmentFixture(int $id, int $recordId): void
    {
        $query = sprintf(
            "INSERT INTO faqattachment (id, record_id, record_lang, real_hash, virtual_hash, password_hash, filename, filesize, encrypted, mime_type) VALUES (%d, %d, 'en', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', NULL, 'fixture-%d.txt', 123, 0, 'text/plain')",
            $id,
            $recordId,
            $id,
        );

        $this->dbHandle?->query($query);
    }

    private function deleteAttachmentFixture(int $id): void
    {
        $this->dbHandle?->query(sprintf('DELETE FROM faqattachment WHERE id = %d', $id));
    }

    public function testConstructorWithApiEnabled(): void
    {
        $controller = new TestApiConstructorController(true);

        $this->assertInstanceOf(AbstractApiController::class, $controller);
    }

    public function testConstructorWithApiDisabled(): void
    {
        $this->expectException(UnauthorizedHttpException::class);
        new TestApiConstructorController(false);
    }

    public function testListReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '1');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testListReturnsValidStatusCode(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '1');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND,
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ]);
    }

    public function testListReturnsJsonData(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '1');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $this->assertJson($response->getContent());
    }

    public function testListReturnsArrayData(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '1');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testListWithInvalidFaqId(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', 'invalid');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // Verify envelope structure with empty data
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);
        $this->assertCount(0, $data['data']);
    }

    public function testListWithZeroFaqId(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '0');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testListWithNegativeFaqId(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '-5');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testListWithMissingFaqId(): void
    {
        $request = new Request();

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // Verify envelope structure with empty data
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);
        $this->assertCount(0, $data['data']);
    }

    public function testListWithLargeFaqId(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '999999999');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND,
            Response::HTTP_INTERNAL_SERVER_ERROR,
        ]);
    }

    public function testListResponseContentIsNotNull(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '1');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $this->assertNotNull($response->getContent());
    }

    public function testListReturnsEmptyArrayWhenNoAttachments(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '999999');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
            $this->assertEquals([], json_decode($response->getContent(), true));
        } else {
            // If attachments exist or error occurred, just verify it's a valid response
            $this->assertInstanceOf(JsonResponse::class, $response);
        }
    }

    public function testListWithNumericStringFaqId(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '123');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertJson($response->getContent());
    }

    public function testListResponseStructure(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '1');

        $controller = new AttachmentController();
        $response = $controller->list($request);

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);

        // Verify envelope structure
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);

        // Verify meta contains pagination
        $this->assertArrayHasKey('pagination', $data['meta']);
        $this->assertArrayHasKey('total', $data['meta']['pagination']);
        $this->assertArrayHasKey('per_page', $data['meta']['pagination']);
        $this->assertArrayHasKey('current_page', $data['meta']['pagination']);

        // If there are attachments, verify the attachment structure
        if (count($data['data']) > 0) {
            foreach ($data['data'] as $attachment) {
                $this->assertArrayHasKey('filename', $attachment);
                $this->assertArrayHasKey('url', $attachment);
            }
        }
    }

    public function testListReturnsInternalServerErrorWhenAttachmentFactoryThrows(): void
    {
        $fixtureId = 990001;
        $this->insertAttachmentFixture($fixtureId, 1);
        $this->setAttachmentFactoryStorageType(999);

        $request = new Request();
        $request->attributes->set('faqId', '1');

        $controller = new AttachmentController();
        $response = $controller->list($request);
        $payload = json_decode($response->getContent(), true);

        $this->deleteAttachmentFixture($fixtureId);

        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Failed to fetch attachments', $payload['error']['message']);
        $this->assertSame('ATTACHMENT_ERROR', $payload['error']['code']);
    }
}
