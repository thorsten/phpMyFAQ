<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\CustomPage;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Faq;
use phpMyFAQ\Instance\Search\Elasticsearch;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(ElasticsearchController::class)]
#[UsesNamespace('phpMyFAQ')]
final class ElasticsearchControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-elasticsearch-controller-');
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

    private function createController(): ElasticsearchController
    {
        return new ElasticsearchController(
            $this->createStub(Elasticsearch::class),
            $this->createStub(Faq::class),
            $this->createStub(CustomPage::class),
        );
    }

    private function createControllerWithElasticsearch(Elasticsearch $elasticsearch): ElasticsearchController
    {
        return new ElasticsearchController(
            $elasticsearch,
            $this->createStub(Faq::class),
            $this->createStub(CustomPage::class),
        );
    }

    private function createAuthenticatedContainer(): ContainerInterface
    {
        $permission = $this->createStub(PermissionInterface::class);
        $permission->method('hasPermission')->willReturnCallback(
            static fn (int $userId, mixed $right): bool => $userId === 42
                && in_array($right, [PermissionType::CONFIGURATION_EDIT, PermissionType::CONFIGURATION_EDIT->value], true)
        );

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

        $session = new Session(new MockArraySessionStorage());

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturnCallback(function (string $id) use ($currentUser, $session) {
            return match ($id) {
                'phpmyfaq.configuration' => $this->configuration,
                'phpmyfaq.user.current_user' => $currentUser,
                'session' => $session,
                default => null,
            };
        });

        return $container;
    }

    /**
     * @throws \Exception
     */
    public function testCreateRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->create();
    }

    /**
     * @throws \Exception
     */
    public function testDropRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->drop();
    }

    /**
     * @throws \Exception
     */
    public function testImportRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->import();
    }

    /**
     * @throws \Exception
     */
    public function testStatisticsRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->statistics();
    }

    /**
     * @throws \Exception
     */
    public function testHealthcheckRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->healthcheck();
    }

    /**
     * @throws \Exception
     */
    public function testHealthcheckReturnsUnavailableWhenElasticsearchIsDown(): void
    {
        $elasticsearch = $this->createMock(Elasticsearch::class);
        $elasticsearch->expects($this->once())->method('isAvailable')->willReturn(false);

        $controller = $this->createControllerWithElasticsearch($elasticsearch);
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->healthcheck();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        self::assertFalse($payload['available']);
        self::assertSame('unavailable', $payload['status']);
    }
}
