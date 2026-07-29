<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Class WebAuthnControllerTest
 */
#[AllowMockObjectsWithoutExpectations]
#[CoversClass(WebAuthnController::class)]
#[UsesNamespace('phpMyFAQ')]
class WebAuthnControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-webauthn-controller-');
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

    public function testConstructorCreatesInstance(): void
    {
        $controller = new WebAuthnController();

        $this->assertInstanceOf(WebAuthnController::class, $controller);
    }

    public function testPrepareReturnsForbiddenWhenWebAuthnDisabled(): void
    {
        $this->configuration->set('security.enableWebAuthnSupport', false);

        $controller = new WebAuthnController();
        $request = new Request([], [], [], [], [], [], json_encode(['username' => 'test']));

        $response = $controller->prepare($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    /**
     * Regression test: the per-login challenge is only useful if it is written back to the user
     * record, otherwise the replay check in authenticate() has nothing to compare against.
     */
    public function testPrepareLoginPersistsTheGeneratedChallenge(): void
    {
        $this->configuration->set('security.enableWebAuthnSupport', true);

        $storedKeys = (string) json_encode([['id' => [1, 2, 3], 'key' => 'public-key-pem']]);

        $persistedKeys = null;
        $user = $this->createMock(\phpMyFAQ\User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('alice')->willReturn(true);
        $user->expects($this->once())->method('getWebAuthnKeys')->willReturn($storedKeys);
        $user
            ->expects($this->once())
            ->method('setWebAuthnKeys')
            ->with($this->callback(static function (string $keys) use (&$persistedKeys): bool {
                $persistedKeys = $keys;

                return true;
            }))
            ->willReturn(true);

        $controller = new WebAuthnController(new \phpMyFAQ\Auth\AuthWebAuthn($this->configuration), $user);
        $request = new Request([], [], [], [], [], [], json_encode(['username' => 'alice']));

        $response = $controller->prepareLogin($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertIsString($persistedKeys, 'The keys carrying the challenge must be persisted.');

        $decoded = json_decode($persistedKeys, false, 512, JSON_THROW_ON_ERROR);
        $this->assertNotEmpty($decoded[0]->challenge ?? '', 'The pending challenge must be persisted.');
    }

    /**
     * Regression test: the challenge consumed by a successful login must be written back so the
     * same assertion cannot be presented twice.
     */
    public function testLoginPersistsTheConsumedChallenge(): void
    {
        $this->configuration->set('security.enableWebAuthnSupport', true);

        $user = $this->createMock(\phpMyFAQ\User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('alice')->willReturn(true);
        $user->expects($this->once())->method('getWebAuthnKeys')->willReturn('stored-keys');
        $user->expects($this->once())->method('setWebAuthnKeys')->willReturn(true);

        $authWebAuthn = $this->createMock(\phpMyFAQ\Auth\AuthWebAuthn::class);
        $authWebAuthn->expects($this->once())->method('authenticate')->willReturn(true);

        $loginUser = $this->createMock(\phpMyFAQ\User\CurrentUser::class);
        $loginUser->expects($this->once())->method('getUserByLogin')->with('alice')->willReturn(true);
        $loginUser->expects($this->once())->method('isBlocked')->willReturn(false);

        $controller = new WebAuthnController($authWebAuthn, $user, $loginUser);
        $request = new Request([], [], [], [], [], [], json_encode([
            'username' => 'alice',
            'login' => ['assertion' => 'payload'],
        ]));

        $response = $controller->login($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testPrepareLoginReturnsForbiddenWhenWebAuthnDisabled(): void
    {
        $this->configuration->set('security.enableWebAuthnSupport', false);

        $controller = new WebAuthnController();
        $request = new Request([], [], [], [], [], [], json_encode(['username' => 'test']));

        $response = $controller->prepareLogin($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testLoginReturnsForbiddenWhenWebAuthnDisabled(): void
    {
        $this->configuration->set('security.enableWebAuthnSupport', false);

        $controller = new WebAuthnController();
        $request = new Request([], [], [], [], [], [], json_encode(['username' => 'test']));

        $response = $controller->login($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testRegisterReturnsForbiddenWhenWebAuthnDisabled(): void
    {
        $this->configuration->set('security.enableWebAuthnSupport', false);

        $controller = new WebAuthnController();
        $request = new Request([], [], [], [], [], [], json_encode(['register' => 'test']));

        $response = $controller->register($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
}
