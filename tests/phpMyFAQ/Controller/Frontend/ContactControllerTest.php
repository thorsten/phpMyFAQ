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
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Seo;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\UserSession;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(ContactController::class)]
#[UsesNamespace('phpMyFAQ')]
final class ContactControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-front-contact-controller-');
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
    public function testIndexPrefillsLoggedInUserData(): void
    {
        $captcha = $this->createStub(CaptchaInterface::class);
        $captchaHelper = $this->createMock(CaptchaHelperInterface::class);
        $captchaHelper
            ->expects(self::once())
            ->method('renderCaptcha')
            ->with($captcha, 'contact', Translation::getString(key: 'msgCaptcha'), true)
            ->willReturn('<div>captcha</div>');

        $userSession = $this->createStub(UserSession::class);
        $currentUser = $this->createLoggedInCurrentUser();

        $controller = new ContactController($userSession, $captcha, $captchaHelper);
        $controller->setContainer($this->createControllerContainer(
            new Session(new MockArraySessionStorage()),
            $currentUser,
        ));

        $response = $controller->index(new Request());

        self::assertStringContainsString('admin@example.com', (string) $response->getContent());
        self::assertStringContainsString('Admin User', (string) $response->getContent());
    }

    private function createControllerContainer(SessionInterface $session, CurrentUser $currentUser): ContainerInterface
    {
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

    private function createLoggedInCurrentUser(): CurrentUser
    {
        $permission = $this->createMock(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn(false);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('isSuperAdmin')->willReturn(false);
        $currentUser->method('getUserId')->willReturn(1);
        $currentUser->method('getLogin')->willReturn('admin');
        $currentUser
            ->method('getUserData')
            ->willReturnMap([
                ['display_name', 'Admin User'],
                ['email',        'admin@example.com'],
            ]);

        return $currentUser;
    }
}
