<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use DateTime;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\CustomPage;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\CustomPageEntity;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Seo;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
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
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(CustomPageController::class)]
#[UsesNamespace('phpMyFAQ')]
final class CustomPageControllerTest extends TestCase
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

        Strings::init('en');
        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();
        $configurationProperty->setValue(null, null);

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-front-custom-page-controller-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->configuration = new Configuration($this->dbHandle);
        $configurationProperty->setValue(null, $this->configuration);

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

    /**
     * @throws \Exception
     */
    public function testShowReturnsNotFoundWhenSlugIsMissing(): void
    {
        $customPage = $this->createStub(CustomPage::class);
        $controller = new CustomPageController($customPage);
        $controller->setContainer($this->createControllerContainer(
            new Session(new MockArraySessionStorage()),
            $this->createLoggedOutCurrentUser(),
        ));

        $response = $controller->show(new Request([], [], ['slug' => '']));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('404', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testShowUsesStoredSeoMetadataWhenAvailable(): void
    {
        $this->dbHandle->query("DELETE FROM faqseo WHERE type = 'page' AND reference_id = 501 AND reference_language = 'en'");
        $this->dbHandle->query(
            "INSERT INTO faqseo (id, type, reference_id, reference_language, title, description, slug, created)
             VALUES (501, 'page', 501, 'en', 'SEO Title', 'SEO Description', 'seo-page', '2026-03-15 10:00:00')"
        );

        $pageEntity = (new CustomPageEntity())
            ->setId(501)
            ->setPageTitle('Rendered Page')
            ->setContent('<p>Page content</p>')
            ->setAuthorName('Test Author')
            ->setAuthorEmail('author@example.com')
            ->setActive(true)
            ->setCreated(new DateTime('2026-03-15 10:00:00'));

        $customPage = $this->createMock(CustomPage::class);
        $customPage
            ->expects(self::once())
            ->method('getBySlug')
            ->with('seo-page', 'en')
            ->willReturn($pageEntity);

        $controller = new CustomPageController($customPage);
        $controller->setContainer($this->createControllerContainer(
            new Session(new MockArraySessionStorage()),
            $this->createLoggedOutCurrentUser(),
        ));

        $response = $controller->show(new Request([], [], ['slug' => 'seo-page']));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('SEO Description', (string) $response->getContent());
        self::assertStringContainsString('Rendered Page', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testShowFallsBackToPageDefaultsWhenSeoDataAreMissing(): void
    {
        $this->dbHandle->query("DELETE FROM faqseo WHERE type = 'page' AND reference_id = 502 AND reference_language = 'en'");

        $pageEntity = (new CustomPageEntity())
            ->setId(502)
            ->setPageTitle('Fallback Page')
            ->setContent('<p>Fallback content</p>')
            ->setAuthorName('Fallback Author')
            ->setAuthorEmail('fallback@example.com')
            ->setActive(true)
            ->setCreated(new DateTime('2026-03-15 10:00:00'));

        $customPage = $this->createMock(CustomPage::class);
        $customPage
            ->expects(self::once())
            ->method('getBySlug')
            ->with('fallback-page', 'en')
            ->willReturn($pageEntity);

        $controller = new CustomPageController($customPage);
        $controller->setContainer($this->createControllerContainer(
            new Session(new MockArraySessionStorage()),
            $this->createLoggedOutCurrentUser(),
        ));

        $response = $controller->show(new Request([], [], ['slug' => 'fallback-page']));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Fallback Page', (string) $response->getContent());
        self::assertStringNotContainsString('SEO Description', (string) $response->getContent());
    }

    private function createControllerContainer(
        SessionInterface $session,
        CurrentUser $currentUser,
    ): ContainerInterface {
        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(function (string $id) use ($currentUser, $session): mixed {
                return match ($id) {
                    'phpmyfaq.configuration' => $this->configuration,
                    'phpmyfaq.user.current_user' => $currentUser,
                    'session' => $session,
                    'phpmyfaq.system' => new System(),
                    'phpmyfaq.seo' => new Seo($this->configuration),
                    default => null,
                };
            });

        return $container;
    }

    private function createLoggedOutCurrentUser(): CurrentUser
    {
        $permission = $this->createMock(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn(false);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(false);
        $currentUser->method('isSuperAdmin')->willReturn(false);
        $currentUser->method('getUserId')->willReturn(-1);
        $currentUser->method('getLogin')->willReturn('');
        $currentUser->method('getUserData')->willReturn('');

        return $currentUser;
    }
}
