<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Mail;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Session\Token;
use phpMyFAQ\StopWords;
use phpMyFAQ\Strings;
use phpMyFAQ\TestDatabaseTrait;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(UserController::class)]
#[UsesNamespace('phpMyFAQ')]
class UserControllerTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;
    private StopWords $stopWords;
    private Mail $mailer;

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

        $this->configuration = $this->createTestConfiguration();

        $this->stopWords = $this->createStub(StopWords::class);
        $this->mailer = $this->createStub(Mail::class);
    }

    protected function tearDown(): void
    {
        unset($_COOKIE['pmf-csrf-token-' . substr(md5('ucp'), 0, 10)]);
        Token::resetInstanceForTests();

        parent::tearDown();
    }

    private function createController(): UserController
    {
        return new UserController($this->stopWords, $this->mailer);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateDataRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'userid' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_visible' => 'on',
            'pmf-csrf-token' => 'test-token',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->updateData($request);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateDataWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->updateData($request);
    }

    /**
     * @throws \Exception
     */
    public function testExportUserDataRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->exportUserData($request);
    }

    /**
     * @throws \Exception
     */
    public function testRequestUserRemovalRequiresValidToken(): void
    {
        $requestData = json_encode([
            'userId' => 1,
            'name' => 'Test User',
            'loginname' => 'testuser',
            'email' => 'test@example.com',
            'question' => 'Please remove my account',
            'pmf-csrf-token' => 'test-token',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->requestUserRemoval($request);
    }

    /**
     * @throws \Exception
     */
    public function testRequestUserRemovalWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->requestUserRemoval($request);
    }

    /**
     * @throws \Exception
     */
    public function testRemoveTwofactorConfigRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrfToken' => 'test-token',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->removeTwofactorConfig($request);
    }

    /**
     * @throws \Exception
     */
    public function testRemoveTwofactorConfigWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->removeTwofactorConfig($request);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateDataIgnoresPasswordFieldsForOidcUser(): void
    {
        $currentUser = $this->createCurrentUserWithAuthSource('oidc');
        $currentUser->expects(self::once())->method('setUserData')->with([
            'is_visible' => 1,
            'twofactor_enabled' => 0,
            'secret' => '',
        ])->willReturn(true);
        $currentUser->expects(self::never())->method('getAuthContainer');
        $currentUser->expects(self::never())->method('revokePersistentLogins');

        $response = $this->runProfileUpdate($currentUser, [
            'faqpassword' => 'new-secret-password',
            'faqpassword_confirm' => 'new-secret-password',
            'faqpassword_current' => 'whatever',
        ]);

        self::assertSame(200, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testUpdateDataKeepsLocalProfileHandlingForKeycloakUser(): void
    {
        $currentUser = $this->createCurrentUserWithAuthSource('keycloak');
        $currentUser->expects(self::once())->method('setUserData')->with([
            'display_name' => 'Test User',
            'is_visible' => 1,
            'email' => '',
            'twofactor_enabled' => 0,
        ])->willReturn(true);

        $response = $this->runProfileUpdate($currentUser, []);

        self::assertSame(200, $response->getStatusCode());
    }

    private function createCurrentUserWithAuthSource(string $authSource): CurrentUser
    {
        $permission = $this->createStub(PermissionInterface::class);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(1);
        $currentUser->method('getUserAuthSource')->willReturn($authSource);
        $currentUser->method('getUserData')->willReturn(0);

        return $currentUser;
    }

    /**
     * @param array<string, string> $extraFields
     * @throws \Exception
     */
    private function runProfileUpdate(CurrentUser $currentUser, array $extraFields): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        Token::resetInstanceForTests();
        $token = Token::getInstance($session)->getTokenString('ucp');
        $_COOKIE['pmf-csrf-token-' . substr(md5('ucp'), 0, 10)] = $token;

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturnCallback(fn(string $id): mixed => match ($id) {
            'phpmyfaq.configuration' => $this->configuration,
            'phpmyfaq.user.current_user' => $currentUser,
            'session' => $session,
            default => null,
        });

        $controller = $this->createController();
        $controller->setContainer($container);

        $payload = json_encode([
            'userid' => 1,
            'name' => 'Test User',
            'is_visible' => 'on',
            'pmf-csrf-token' => $token,
        ] + $extraFields);

        return $controller->updateData(new Request([], [], [], [], [], [], $payload));
    }
}
