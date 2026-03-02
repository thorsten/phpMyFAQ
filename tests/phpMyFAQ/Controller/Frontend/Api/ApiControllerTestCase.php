<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[CoversNothing]
abstract class ApiControllerTestCase extends TestCase
{
    protected Configuration $configuration;
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

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_TIME'] = time();
        $_COOKIE = [];
        Token::resetInstanceForTests();

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();
        $configurationProperty->setValue(null, null);

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-frontend-api-controller-');
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

        $this->dbHandle->close();
        @unlink($this->databasePath);
        Token::resetInstanceForTests();
        $_COOKIE = [];

        parent::tearDown();
    }

    protected function createSession(): Session
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        return $session;
    }

    protected function createAuthenticatedUserMock(int $userId = 1): CurrentUser
    {
        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn($userId);
        $currentUser->method('getUserAuthSource')->willReturn('local');
        $currentUser->method('getUserById')->willReturn(true);
        $currentUser->method('getLogin')->willReturn('testuser');
        $currentUser
            ->method('getUserData')
            ->willReturnMap([
                ['email',             'test@example.com'],
                ['display_name',      'Test User'],
                ['last_modified',     '20260301120000'],
                ['is_visible',        1],
                ['twofactor_enabled', 0],
                ['secret',            ''],
            ]);

        return $currentUser;
    }

    protected function injectControllerState(
        object $controller,
        ?CurrentUser $currentUser = null,
        ?SessionInterface $session = null,
    ): void {
        if ($currentUser instanceof CurrentUser) {
            $currentUserProperty = new \ReflectionProperty($controller, 'currentUser');
            $currentUserProperty->setValue($controller, $currentUser);
        }

        if ($session instanceof SessionInterface) {
            $sessionProperty = new \ReflectionProperty($controller, 'session');
            $sessionProperty->setValue($controller, $session);
        }

        $configurationProperty = new \ReflectionProperty($controller, 'configuration');
        $configurationProperty->setValue($controller, $this->configuration);
    }

    protected function overrideConfigurationValues(array $values): void
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
