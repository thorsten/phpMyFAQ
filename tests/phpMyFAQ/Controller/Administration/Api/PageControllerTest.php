<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Instance\Search\Elasticsearch;
use phpMyFAQ\Instance\Search\OpenSearch;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(PageController::class)]
#[UsesNamespace('phpMyFAQ')]
final class PageControllerTest extends TestCase
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
        Token::resetInstanceForTests();

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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-page-controller-');
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
        Token::resetInstanceForTests();
        unset($_COOKIE['pmf-csrf-token-' . substr(md5('save-page'), 0, 10)]);
        unset($_COOKIE['pmf-csrf-token-' . substr(md5('delete-page'), 0, 10)]);
        unset($_COOKIE['pmf-csrf-token-' . substr(md5('update-page'), 0, 10)]);
        unset($_COOKIE['pmf-csrf-token-' . substr(md5('activate-page'), 0, 10)]);

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

    private function createController(): PageController
    {
        return new PageController($this->createStub(Elasticsearch::class), $this->createStub(OpenSearch::class));
    }

    private function createControllerWithSearchDependencies(
        Elasticsearch $elasticsearch,
        OpenSearch $openSearch,
    ): PageController {
        return new PageController($elasticsearch, $openSearch);
    }

    /**
     * @throws \Exception
     */
    public function testCreateRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrfToken' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrfToken' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrfToken' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->update($request);
    }

    /**
     * @throws \Exception
     */
    public function testActivateRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrfToken' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->activate($request);
    }

    /**
     * @throws \Exception
     */
    public function testCheckSlugRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrfToken' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->checkSlug($request);
    }

    /**
     * @throws \Exception
     */
    public function testListRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->list($request);
    }

    /**
     * @throws \Exception
     */
    public function testCreateWithInvalidJsonStillRequiresAuthenticationFirst(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsBadRequestForInvalidJsonWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->create(new Request([], [], [], [], [], [], 'invalid json'));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertStringContainsString('Invalid JSON', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsUnauthorizedForInvalidCsrfTokenWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->delete(new Request([], [], [], [], [], [], json_encode([
            'id' => 1,
            'lang' => 'en',
            'csrfToken' => 'invalid-token',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->update(new Request([], [], [], [], [], [], json_encode([
            'id' => 1,
            'pageTitle' => 'Updated title',
            'slug' => 'updated-title',
            'authorName' => 'Author',
            'authorEmail' => 'author@example.com',
            'lang' => 'en',
            'csrfToken' => 'invalid-token',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testActivateReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->activate(new Request([], [], [], [], [], [], json_encode([
            'id' => 1,
            'status' => true,
            'csrfToken' => 'invalid-token',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testCheckSlugReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->checkSlug(new Request([], [], [], [], [], [], json_encode([
            'slug' => 'test-page',
            'lang' => 'en',
            'csrfToken' => 'invalid-token',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testListReturnsPaginatedPayloadWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->list(new Request(['limit' => '5', 'offset' => '0']));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertIsArray($payload['data']);
        self::assertSame(5, $payload['limit']);
        self::assertSame(0, $payload['offset']);
        self::assertArrayHasKey('total', $payload);
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsBadRequestForMissingRequiredFieldWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'save-page');
        $controller->setContainer($container);

        $response = $controller->create(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'pageTitle' => 'Test page',
            'slug' => 'test-page',
            'authorName' => 'Author',
            'authorEmail' => '',
            'lang' => 'en',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Missing required field: authorEmail', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsConflictWhenSlugAlreadyExists(): void
    {
        $existingSlug = 'existing-page-' . bin2hex(random_bytes(4));
        $pageId = $this->createPageViaController($existingSlug);
        self::assertGreaterThan(0, $pageId);

        $controller = $this->createController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'save-page');
        $controller->setContainer($container);

        $response = $controller->create(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'pageTitle' => 'Duplicate slug page',
            'slug' => $existingSlug,
            'content' => 'Some content',
            'authorName' => 'Author',
            'authorEmail' => 'author@example.com',
            'active' => true,
            'lang' => 'en',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        self::assertSame(Translation::get('ad_page_slug_exists'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsConflictWhenTranslationLanguageAlreadyExists(): void
    {
        $existingSlug = 'existing-translation-page-' . bin2hex(random_bytes(4));
        $pageId = $this->createPageViaController($existingSlug);
        self::assertGreaterThan(0, $pageId);

        $controller = $this->createController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'save-page');
        $controller->setContainer($container);

        $response = $controller->create(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'pageId' => $pageId,
            'pageTitle' => 'Duplicate translation',
            'slug' => 'duplicate-translation-' . bin2hex(random_bytes(3)),
            'content' => 'Translated content',
            'authorName' => 'Author',
            'authorEmail' => 'author@example.com',
            'active' => true,
            'lang' => 'en',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        self::assertSame('Translation for this language already exists', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsBadRequestForMissingRequiredFieldsWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'delete-page');
        $controller->setContainer($container);

        $response = $controller->delete(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'id' => 1,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Missing required fields: id, lang', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsBadRequestForMissingRequiredFieldWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'update-page');
        $controller->setContainer($container);

        $response = $controller->update(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'id' => 1,
            'pageTitle' => 'Updated title',
            'slug' => 'updated-title',
            'authorName' => 'Author',
            'authorEmail' => '',
            'lang' => 'en',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Missing required field: authorEmail', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testActivateReturnsBadRequestForMissingRequiredFieldsWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'activate-page');
        $controller->setContainer($container);

        $response = $controller->activate(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'id' => 1,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Missing required fields: id, status', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testCheckSlugReturnsBadRequestForMissingFieldsWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'save-page');
        $controller->setContainer($container);

        $response = $controller->checkSlug(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'slug' => 'test-page',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Missing required fields: slug, lang', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testCheckSlugReturnsAvailabilityWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'save-page');
        $controller->setContainer($container);

        $response = $controller->checkSlug(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'slug' => 'fresh-page-slug',
            'lang' => 'en',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($payload['available']);
        self::assertSame('fresh-page-slug', $payload['slug']);
    }

    /**
     * @throws \Exception
     */
    public function testCheckSlugReturnsUnavailableForExistingSlug(): void
    {
        $existingSlug = 'existing-check-slug-' . bin2hex(random_bytes(4));
        $pageId = $this->createPageViaController($existingSlug);
        self::assertGreaterThan(0, $pageId);

        $controller = $this->createController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'save-page');
        $controller->setContainer($container);

        $response = $controller->checkSlug(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'slug' => $existingSlug,
            'lang' => 'en',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertFalse($payload['available']);
        self::assertSame($existingSlug, $payload['slug']);
    }

    /**
     * @throws \Exception
     */
    private function createValidCsrfToken(Session $session, string $page): string
    {
        Token::resetInstanceForTests();
        $token = Token::getInstance($session)->getTokenString($page);
        $_COOKIE['pmf-csrf-token-' . substr(md5($page), 0, 10)] = $token;

        return $token;
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsSuccessAndIndexesWhenSearchIsEnabled(): void
    {
        $this->configuration->set('search.enableElasticsearch', true);
        $this->configuration->set('search.enableOpenSearch', true);

        $elasticsearch = $this->createMock(Elasticsearch::class);
        $elasticsearch->expects($this->once())->method('indexCustomPage');

        $openSearch = $this->createMock(OpenSearch::class);
        $openSearch->expects($this->once())->method('indexCustomPage');

        $controller = $this->createControllerWithSearchDependencies($elasticsearch, $openSearch);
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'save-page');
        $controller->setContainer($container);

        $slug = 'unit-test-create-' . bin2hex(random_bytes(4));
        $response = $controller->create(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'pageTitle' => 'Unit Test Page',
            'slug' => $slug,
            'content' => 'Some content',
            'authorName' => 'Test Author',
            'authorEmail' => 'test@example.com',
            'active' => true,
            'lang' => 'en',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('id', $payload);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsSuccessAndUpdatesIndexesWhenSearchIsEnabled(): void
    {
        $this->configuration->set('search.enableElasticsearch', true);
        $this->configuration->set('search.enableOpenSearch', true);

        $pageId = $this->createPageViaController('unit-test-update-' . bin2hex(random_bytes(4)));

        $elasticsearch = $this->createMock(Elasticsearch::class);
        $elasticsearch->expects($this->once())->method('updateCustomPage');

        $openSearch = $this->createMock(OpenSearch::class);
        $openSearch->expects($this->once())->method('updateCustomPage');

        $controller = $this->createControllerWithSearchDependencies($elasticsearch, $openSearch);
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'update-page');
        $controller->setContainer($container);

        $response = $controller->update(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'id' => $pageId,
            'pageTitle' => 'Updated Unit Test Page',
            'slug' => 'updated-unit-test-' . $pageId,
            'content' => 'Updated content',
            'authorName' => 'Test Author',
            'authorEmail' => 'test@example.com',
            'active' => true,
            'lang' => 'en',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
    }

    /**
     * @throws \Exception
     */
    public function testActivateReturnsSuccessAndUpdatesIndexesWhenSearchIsEnabled(): void
    {
        $this->configuration->set('search.enableElasticsearch', true);
        $this->configuration->set('search.enableOpenSearch', true);

        $pageId = $this->createPageViaController('unit-test-activate-' . bin2hex(random_bytes(4)));

        $elasticsearch = $this->createMock(Elasticsearch::class);
        $elasticsearch->expects($this->once())->method('updateCustomPage');

        $openSearch = $this->createMock(OpenSearch::class);
        $openSearch->expects($this->once())->method('updateCustomPage');

        $controller = $this->createControllerWithSearchDependencies($elasticsearch, $openSearch);
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'activate-page');
        $controller->setContainer($container);

        $response = $controller->activate(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'id' => $pageId,
            'status' => false,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsSuccessAndDeletesFromIndexesWhenSearchIsEnabled(): void
    {
        $this->configuration->set('search.enableElasticsearch', true);
        $this->configuration->set('search.enableOpenSearch', true);

        $pageId = $this->createPageViaController('unit-test-delete-' . bin2hex(random_bytes(4)));

        $elasticsearch = $this->createMock(Elasticsearch::class);
        $elasticsearch->expects($this->once())->method('deleteCustomPage')->with($pageId, 'en');

        $openSearch = $this->createMock(OpenSearch::class);
        $openSearch->expects($this->once())->method('deleteCustomPage')->with($pageId, 'en');

        $controller = $this->createControllerWithSearchDependencies($elasticsearch, $openSearch);
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'delete-page');
        $controller->setContainer($container);

        $response = $controller->delete(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'id' => $pageId,
            'lang' => 'en',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
    }

    /**
     * @throws \Exception
     */
    public function testCreateStillSucceedsWhenSearchIndexingThrows(): void
    {
        $this->configuration->set('search.enableElasticsearch', true);
        $this->configuration->set('search.enableOpenSearch', true);

        $elasticsearch = $this->createMock(Elasticsearch::class);
        $elasticsearch->expects($this->once())->method('indexCustomPage')->willThrowException(new \Exception('ES down'));

        $openSearch = $this->createMock(OpenSearch::class);
        $openSearch->expects($this->once())->method('indexCustomPage')->willThrowException(new \Exception('OS down'));

        $controller = $this->createControllerWithSearchDependencies($elasticsearch, $openSearch);
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'save-page');
        $controller->setContainer($container);

        $slug = 'unit-test-create-error-' . bin2hex(random_bytes(4));
        $response = $controller->create(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'pageTitle' => 'Unit Test Page With Search Errors',
            'slug' => $slug,
            'content' => 'Some content',
            'authorName' => 'Test Author',
            'authorEmail' => 'test@example.com',
            'active' => true,
            'lang' => 'en',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('id', $payload);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateStillSucceedsWhenSearchUpdatesThrow(): void
    {
        $this->configuration->set('search.enableElasticsearch', true);
        $this->configuration->set('search.enableOpenSearch', true);

        $pageId = $this->createPageViaController('unit-test-update-error-' . bin2hex(random_bytes(4)));

        $elasticsearch = $this->createMock(Elasticsearch::class);
        $elasticsearch->expects($this->once())->method('updateCustomPage')->willThrowException(new \Exception('ES down'));

        $openSearch = $this->createMock(OpenSearch::class);
        $openSearch->expects($this->once())->method('updateCustomPage')->willThrowException(new \Exception('OS down'));

        $controller = $this->createControllerWithSearchDependencies($elasticsearch, $openSearch);
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'update-page');
        $controller->setContainer($container);

        $response = $controller->update(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'id' => $pageId,
            'pageTitle' => 'Updated Unit Test Page With Search Errors',
            'slug' => 'updated-unit-test-error-' . $pageId,
            'content' => 'Updated content',
            'authorName' => 'Test Author',
            'authorEmail' => 'test@example.com',
            'active' => true,
            'lang' => 'en',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteStillSucceedsWhenSearchDeletesThrow(): void
    {
        $this->configuration->set('search.enableElasticsearch', true);
        $this->configuration->set('search.enableOpenSearch', true);

        $pageId = $this->createPageViaController('unit-test-delete-error-' . bin2hex(random_bytes(4)));

        $elasticsearch = $this->createMock(Elasticsearch::class);
        $elasticsearch->expects($this->once())->method('deleteCustomPage')->with($pageId, 'en')
            ->willThrowException(new \Exception('ES down'));

        $openSearch = $this->createMock(OpenSearch::class);
        $openSearch->expects($this->once())->method('deleteCustomPage')->with($pageId, 'en')
            ->willThrowException(new \Exception('OS down'));

        $controller = $this->createControllerWithSearchDependencies($elasticsearch, $openSearch);
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'delete-page');
        $controller->setContainer($container);

        $response = $controller->delete(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'id' => $pageId,
            'lang' => 'en',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
    }

    /**
     * @throws \Exception
     */
    private function createPageViaController(string $slug): int
    {
        $controller = $this->createController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'save-page');
        $controller->setContainer($container);

        $response = $controller->create(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'pageTitle' => 'Seed page',
            'slug' => $slug,
            'content' => 'Seed content',
            'authorName' => 'Seeder',
            'authorEmail' => 'seed@example.com',
            'active' => true,
            'lang' => 'en',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        return (int) $payload['id'];
    }

    private function createAuthenticatedContainer(): ContainerInterface
    {
        $permission = $this->createMock(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(static function (int $userId, mixed $right): bool {
                return $userId === 42
                && in_array(
                    $right,
                    [
                        PermissionType::PAGE_ADD->value,
                        PermissionType::PAGE_EDIT->value,
                        PermissionType::PAGE_DELETE->value,
                    ],
                    true,
                );
            });

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

        $session = new Session(new MockArraySessionStorage());
        $adminLog = $this->createStub(AdminLog::class);

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(function (string $id) use ($currentUser, $session, $adminLog) {
                return match ($id) {
                    'phpmyfaq.configuration' => $this->configuration,
                    'phpmyfaq.user.current_user' => $currentUser,
                    'session' => $session,
                    'phpmyfaq.admin.admin-log' => $adminLog,
                    default => null,
                };
            });

        return $container;
    }
}
