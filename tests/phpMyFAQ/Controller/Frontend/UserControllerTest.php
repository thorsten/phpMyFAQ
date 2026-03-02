<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Captcha\CaptchaInterface;
use phpMyFAQ\Captcha\Helper\CaptchaHelperInterface;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Service\Gravatar;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\UserSession;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(UserController::class)]
#[UsesNamespace('phpMyFAQ')]
final class UserControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-user-controller-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->configuration = new Configuration($this->dbHandle);
        $this->initializeDatabaseStatics($this->dbHandle);

        $language = new Language($this->configuration, new Session(new MockArraySessionStorage()));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);
    }

    protected function tearDown(): void
    {
        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        if (isset($this->dbHandle)) {
            $this->dbHandle->close();
        }

        if (isset($this->databasePath) && is_file($this->databasePath)) {
            unlink($this->databasePath);
        }

        parent::tearDown();
    }

    public function testRequestRemovalRendersForLoggedInUser(): void
    {
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'main.privacyURL' => 'https://localhost/privacy.html',
        ]);

        $controller = $this->createController();
        $this->setCurrentUser($controller, $this->createLoggedInCurrentUser());

        $response = $controller->requestRemoval(Request::create('/user/request-removal', 'GET'));

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('id="pmf-request-removal-form"', (string) $response->getContent());
        self::assertStringContainsString('name="userId" id="userId" value="1"', (string) $response->getContent());
    }

    public function testBookmarksRendersForLoggedInUser(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);

        $controller = $this->createController();
        $this->setCurrentUser($controller, $this->createLoggedInCurrentUser());

        $response = $controller->bookmarks(Request::create('/user/bookmarks', 'GET'));

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('id="pmf-bookmarks-delete-all"', (string) $response->getContent());
        self::assertStringContainsString('id="bookmarkAccordion"', (string) $response->getContent());
    }

    public function testUcpRendersForLoggedInUser(): void
    {
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'main.enableGravatarSupport' => false,
            'security.enableWebAuthnSupport' => false,
        ]);

        $controller = $this->createController();
        $this->setCurrentUser($controller, $this->createLoggedInCurrentUser());

        $response = $controller->ucp(Request::create('/user/ucp', 'GET'));

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('id="pmf-user-control-panel-form"', (string) $response->getContent());
        self::assertStringContainsString('id="pmf-submit-user-control-panel"', (string) $response->getContent());
    }

    private function createController(): UserController
    {
        $captcha = $this->createMock(CaptchaInterface::class);
        $captchaHelper = $this->createMock(CaptchaHelperInterface::class);
        $gravatar = $this->createMock(Gravatar::class);

        return new UserController(new UserSession($this->configuration), $captcha, $captchaHelper, $gravatar);
    }

    private function createLoggedInCurrentUser(): CurrentUser
    {
        $currentUser = new CurrentUser($this->configuration);
        $currentUser->getUserById(1, true);
        $currentUser->setLoggedIn(true);

        return $currentUser;
    }

    private function setCurrentUser(UserController $controller, CurrentUser $currentUser): void
    {
        $property = new \ReflectionProperty($controller, 'currentUser');
        $property->setValue($controller, $currentUser);
    }

    private function overrideConfigurationValues(array $values): void
    {
        $reflection = new \ReflectionClass(Configuration::class);
        $configProperty = $reflection->getProperty('config');
        $currentConfig = $configProperty->getValue($this->configuration);
        self::assertIsArray($currentConfig);

        $configProperty->setValue($this->configuration, array_merge($currentConfig, $values));
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
}
