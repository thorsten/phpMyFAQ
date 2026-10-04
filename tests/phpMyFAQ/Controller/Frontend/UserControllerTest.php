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
use phpMyFAQ\User\PasswordResetTokenService;
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
        // The constructor only registers the very first instance of the process as the
        // singleton; install this one explicitly so the controller under test uses it.
        $configurationProperty->setValue(null, $this->configuration);
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
            $databaseReflection = new \ReflectionClass(Database::class);
            $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
            $databaseDriverProperty->setValue(null, null);
            $dbTypeProperty = $databaseReflection->getProperty('dbType');
            $dbTypeProperty->setValue(null, '');
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

    public function testUcpExposesTheSecretOnlyDuringEnrolment(): void
    {
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'main.enableGravatarSupport' => false,
            'security.enableWebAuthnSupport' => false,
        ]);
        $this->dbHandle->query(
            "UPDATE faquserdata SET twofactor_enabled = 0, secret = 'ENROLSECRET123' WHERE user_id = 1",
        );

        $controller = $this->createController();
        $this->setCurrentUser($controller, $this->createLoggedInCurrentUser());

        $content = (string) $controller->ucp(Request::create('/user/ucp', 'GET'))->getContent();

        self::assertStringContainsString('ENROLSECRET123', $content);
        self::assertStringContainsString('id="twofactor_config"', $content);
        self::assertStringContainsString('data:image/png;base64,', $content);
        self::assertStringNotContainsString('id="removeCurrentConfig"', $content);
    }

    public function testUcpNeverRendersTheSecretOnceTwoFactorIsEnabled(): void
    {
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'main.enableGravatarSupport' => false,
            'security.enableWebAuthnSupport' => false,
        ]);
        $this->dbHandle->query(
            "UPDATE faquserdata SET twofactor_enabled = 1, secret = 'ACTIVESECRET123' WHERE user_id = 1",
        );

        $controller = $this->createController();
        $this->setCurrentUser($controller, $this->createLoggedInCurrentUser());

        $content = (string) $controller->ucp(Request::create('/user/ucp', 'GET'))->getContent();

        self::assertStringNotContainsString('ACTIVESECRET123', $content);
        self::assertStringNotContainsString('id="twofactor_config"', $content);
        self::assertStringNotContainsString('data:image/png;base64,', $content);
        self::assertStringContainsString('id="removeCurrentConfig"', $content);
        self::assertStringContainsString(Translation::get('msgTwofactorAlreadyConfigured'), $content);

        $result = $this->dbHandle->query('SELECT secret FROM faquserdata WHERE user_id = 1');
        self::assertSame('ACTIVESECRET123', $this->dbHandle->fetchArray($result)['secret']);
    }

    public function testResetPasswordRendersTheFormForAValidToken(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);
        $user = new CurrentUser($this->configuration);
        self::assertTrue($user->getUserById(1, true));
        $token = new PasswordResetTokenService()->issue(1, $user->getEncryptedPassword());

        $controller = $this->createController();
        $response = $controller->resetPassword(Request::create('/user/password/reset', 'GET', [
            'u' => '1',
            'exp' => (string) $token['expires'],
            'sig' => $token['signature'],
        ]));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = (string) $response->getContent();
        self::assertStringContainsString('id="pmf-resetpw-form"', $content);
        self::assertStringContainsString('name="u" value="1"', $content);
        self::assertStringContainsString('name="exp" value="' . $token['expires'] . '"', $content);
        self::assertStringContainsString('name="sig" value="' . $token['signature'] . '"', $content);
    }

    public function testResetPasswordRejectsAForgedSignature(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);

        $controller = $this->createController();
        $response = $controller->resetPassword(Request::create('/user/password/reset', 'GET', [
            'u' => '1',
            'exp' => (string) (time() + 3600),
            'sig' => str_repeat('0', 64),
        ]));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = (string) $response->getContent();
        self::assertStringNotContainsString('id="pmf-resetpw-form"', $content);
        self::assertStringContainsString(Translation::get('resetpwd_err_invalid'), $content);
    }

    public function testResetPasswordRejectsAnUnknownUserAndMissingParameters(): void
    {
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);
        $controller = $this->createController();

        $unknownUser = $controller->resetPassword(Request::create('/user/password/reset', 'GET', [
            'u' => '424242',
            'exp' => (string) (time() + 3600),
            'sig' => str_repeat('0', 64),
        ]));
        $missing = $controller->resetPassword(Request::create('/user/password/reset', 'GET'));

        self::assertStringNotContainsString('id="pmf-resetpw-form"', (string) $unknownUser->getContent());
        self::assertStringNotContainsString('id="pmf-resetpw-form"', (string) $missing->getContent());
    }

    public function testUcpRendersTheGravatarWhenEnabled(): void
    {
        $this->overrideConfigurationValues([
            'main.enableUserTracking' => false,
            'security.enableWebAuthnSupport' => false,
        ]);
        // Configuration::get() reloads the whole array from the database as soon as a key is
        // missing, which would discard an in-memory override of this flag.
        self::assertTrue($this->configuration->set('main.enableGravatarSupport', 'true'));

        $gravatar = $this->createMock(Gravatar::class);
        $gravatar
            ->expects($this->once())
            ->method('getImage')
            ->with($this->isString(), ['class' => 'img-responsive rounded-circle', 'size' => '125'])
            ->willReturn('<img src="https://www.gravatar.com/avatar/abc" alt="">');

        $controller = new UserController(
            new UserSession($this->configuration),
            $this->createMock(CaptchaInterface::class),
            $this->createMock(CaptchaHelperInterface::class),
            $gravatar,
        );
        $this->setCurrentUser($controller, $this->createLoggedInCurrentUser());

        $response = $controller->ucp(Request::create('/user/ucp', 'GET'));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('href="https://www.gravatar.com"', (string) $response->getContent());
        self::assertStringContainsString('gravatar.com/avatar/abc', (string) $response->getContent());
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
