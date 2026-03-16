<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use Exception;
use phpMyFAQ\Auth\AuthEntraId;
use phpMyFAQ\Auth\EntraId\EntraIdSession;
use phpMyFAQ\Auth\EntraId\OAuth;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception as CoreException;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(AzureAuthenticationController::class)]
#[UsesNamespace('phpMyFAQ')]
class AzureAuthenticationControllerTest extends TestCase
{
    private Configuration $configuration;

    /**
     * @throws CoreException
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

        try {
            $this->configuration = Configuration::getConfigurationInstance();
        } catch (\TypeError) {
            $dbHandle = new Sqlite3();
            $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
            $this->configuration = new Configuration($dbHandle);
        }
    }

    public function testAuthorizeReturnsRedirectResponse(): void
    {
        $auth = $this->createMock(AuthEntraId::class);
        $auth
            ->expects($this->once())
            ->method('authorize')
            ->willReturn(new RedirectResponse('https://login.microsoftonline.com/test'));

        $controller = new AzureAuthenticationController(
            authContextFactory: fn(): array => [
                $auth,
                $this->createMock(OAuth::class),
                $this->createMock(EntraIdSession::class),
            ],
            azureConfigLoader: static fn(): null => null,
        );

        $response = $controller->authorize();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://login.microsoftonline.com/test', $response->headers->get('Location'));
    }

    public function testAuthorizeFallsBackToDefaultRedirectOnException(): void
    {
        $controller = new AzureAuthenticationController(
            authContextFactory: static function (): array {
                throw new Exception('Authorization failed');
            },
            azureConfigLoader: static fn(): null => null,
        );

        $response = $controller->authorize();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($this->configuration->getDefaultUrl(), $response->headers->get('Location'));
    }

    public function testLogoutReturnsRedirectResponse(): void
    {
        $auth = $this->createMock(AuthEntraId::class);
        $auth
            ->expects($this->once())
            ->method('logout')
            ->willReturn(new RedirectResponse('https://login.microsoftonline.com/common/wsfederation?wa=wsignout1.0'));

        $controller = new AzureAuthenticationController(
            authContextFactory: fn(): array => [
                $auth,
                $this->createMock(OAuth::class),
                $this->createMock(EntraIdSession::class),
            ],
            azureConfigLoader: static fn(): null => null,
        );

        $response = $controller->logout();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('wa=wsignout1.0', (string) $response->headers->get('Location'));
    }

    /**
     * @throws Exception
     */
    public function testCallbackReturnsErrorResponseWhenProviderErrorIsSet(): void
    {
        $controller = new AzureAuthenticationController(
            authContextFactory: fn(): array => [
                $this->createMock(AuthEntraId::class),
                $this->createMock(OAuth::class),
                $this->createMock(EntraIdSession::class),
            ],
            azureConfigLoader: static fn(): null => null,
        );

        $request = new Request(['error_description' => 'Denied by provider']);
        $response = $controller->callback($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('Denied by provider', $response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testCallbackReturnsDefaultRedirectWhenSessionKeyIsMissing(): void
    {
        $entraIdSession = $this->createMock(EntraIdSession::class);
        $entraIdSession->expects($this->once())->method('getCurrentSessionKey')->willReturn(null);

        $controller = new AzureAuthenticationController(
            authContextFactory: fn(): array => [
                $this->createMock(AuthEntraId::class),
                $this->createMock(OAuth::class),
                $entraIdSession,
            ],
            azureConfigLoader: static fn(): null => null,
        );

        $response = $controller->callback(new Request(['code' => 'abc']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testCallbackStoresUserSessionDataOnSuccessfulLogin(): void
    {
        $token = new stdClass();
        $token->access_token = 'access';
        $token->refresh_token = 'refresh';
        $token->id_token = 'a.b.c';

        $oauth = $this->createMock(OAuth::class);
        $oauth->expects($this->once())->method('getOAuthToken')->with('test-code')->willReturn($token);
        $oauth->expects($this->once())->method('setToken')->with($token)->willReturnSelf();
        $oauth->expects($this->once())->method('setAccessToken')->with('access')->willReturnSelf();
        $oauth->expects($this->once())->method('setRefreshToken')->with('refresh')->willReturnSelf();
        $oauth->expects($this->exactly(3))->method('getMail')->willReturn('john@example.com');
        $oauth->expects($this->once())->method('getRefreshToken')->willReturn('refresh');
        $oauth->expects($this->once())->method('getAccessToken')->willReturn('access');
        $oauth->expects($this->once())->method('getToken')->willReturn(new stdClass());

        $auth = $this->createMock(AuthEntraId::class);
        $auth->expects($this->once())->method('isValidLogin')->with('john@example.com')->willReturn(1);
        $auth->expects($this->once())->method('checkCredentials')->with('john@example.com', '')->willReturn(true);

        $entraIdSession = $this->createMock(EntraIdSession::class);
        $entraIdSession->expects($this->once())->method('getCurrentSessionKey')->willReturn('session-key');
        $entraIdSession
            ->expects($this->once())
            ->method('get')
            ->with(EntraIdSession::ENTRA_ID_OAUTH_VERIFIER)
            ->willReturn('verifier');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->expects($this->once())->method('getUserByLogin')->with('john@example.com');
        $currentUser->expects($this->once())->method('setLoggedIn')->with(true);
        $currentUser->expects($this->once())->method('setAuthSource')->with('azure');
        $currentUser->expects($this->once())->method('updateSessionId')->with(true);
        $currentUser->expects($this->once())->method('saveToSession');
        $currentUser->expects($this->once())->method('setTokenData');
        $currentUser->expects($this->once())->method('setSuccess')->with(true);

        $controller = new AzureAuthenticationController(
            authContextFactory: fn(): array => [$auth, $oauth, $entraIdSession],
            currentUserFactory: fn(): CurrentUser => $currentUser,
            azureConfigLoader: static fn(): null => null,
        );

        $response = $controller->callback(new Request(['code' => 'test-code']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testCallbackReturnsResponseWhenLoginIsNotValid(): void
    {
        $token = new stdClass();
        $token->access_token = 'access';
        $token->refresh_token = 'refresh';

        $oauth = $this->createMock(OAuth::class);
        $oauth->expects($this->once())->method('getOAuthToken')->with('test-code')->willReturn($token);
        $oauth->expects($this->once())->method('setToken')->with($token)->willReturnSelf();
        $oauth->expects($this->once())->method('setAccessToken')->with('access')->willReturnSelf();
        $oauth->expects($this->once())->method('setRefreshToken')->with('refresh')->willReturnSelf();
        $oauth->expects($this->once())->method('getMail')->willReturn('john@example.com');

        $auth = $this->createMock(AuthEntraId::class);
        $auth->expects($this->once())->method('isValidLogin')->with('john@example.com')->willReturn(0);
        $auth->expects($this->never())->method('checkCredentials');

        $entraIdSession = $this->createMock(EntraIdSession::class);
        $entraIdSession->expects($this->once())->method('getCurrentSessionKey')->willReturn('session-key');

        $controller = new AzureAuthenticationController(
            authContextFactory: fn(): array => [$auth, $oauth, $entraIdSession],
            azureConfigLoader: static fn(): null => null,
        );

        $response = $controller->callback(new Request(['code' => 'test-code']));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('Login not valid.', $response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testCallbackReturnsResponseWhenCredentialsAreNotValid(): void
    {
        $token = new stdClass();
        $token->access_token = 'access';
        $token->refresh_token = 'refresh';

        $oauth = $this->createMock(OAuth::class);
        $oauth->expects($this->once())->method('getOAuthToken')->with('test-code')->willReturn($token);
        $oauth->expects($this->once())->method('setToken')->with($token)->willReturnSelf();
        $oauth->expects($this->once())->method('setAccessToken')->with('access')->willReturnSelf();
        $oauth->expects($this->once())->method('setRefreshToken')->with('refresh')->willReturnSelf();
        $oauth->expects($this->exactly(2))->method('getMail')->willReturn('john@example.com');

        $auth = $this->createMock(AuthEntraId::class);
        $auth->expects($this->once())->method('isValidLogin')->with('john@example.com')->willReturn(1);
        $auth->expects($this->once())->method('checkCredentials')->with('john@example.com', '')->willReturn(false);

        $entraIdSession = $this->createMock(EntraIdSession::class);
        $entraIdSession->expects($this->once())->method('getCurrentSessionKey')->willReturn('session-key');

        $controller = new AzureAuthenticationController(
            authContextFactory: fn(): array => [$auth, $oauth, $entraIdSession],
            azureConfigLoader: static fn(): null => null,
        );

        $response = $controller->callback(new Request(['code' => 'test-code']));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('Credentials not valid.', $response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testCallbackReturnsFailureResponseWhenOAuthTokenExchangeThrows(): void
    {
        $oauth = $this->createMock(OAuth::class);
        $oauth
            ->expects($this->once())
            ->method('getOAuthToken')
            ->with('test-code')
            ->willThrowException(new Exception('Token exchange failed'));

        $entraIdSession = $this->createMock(EntraIdSession::class);
        $entraIdSession->expects($this->once())->method('getCurrentSessionKey')->willReturn('session-key');

        $controller = new AzureAuthenticationController(
            authContextFactory: fn(): array => [
                $this->createMock(AuthEntraId::class),
                $oauth,
                $entraIdSession,
            ],
            azureConfigLoader: static fn(): null => null,
        );

        $response = $controller->callback(new Request(['code' => 'test-code']));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertStringContainsString(
            'Entra ID Login failed: Token exchange failed',
            (string) $response->getContent(),
        );
    }

    public function testBuildAuthContextReturnsDefaultServices(): void
    {
        $controller = new AzureAuthenticationController();

        $reflectionMethod = new \ReflectionMethod(AzureAuthenticationController::class, 'buildAuthContext');
        $result = $reflectionMethod->invoke($controller);

        $this->assertIsArray($result);
        $this->assertInstanceOf(AuthEntraId::class, $result[0]);
        $this->assertInstanceOf(OAuth::class, $result[1]);
        $this->assertInstanceOf(EntraIdSession::class, $result[2]);
    }

    public function testGetCurrentUserServiceReturnsCurrentUserFromContainer(): void
    {
        $controller = new AzureAuthenticationController();

        $reflectionMethod = new \ReflectionMethod(AzureAuthenticationController::class, 'getCurrentUserService');
        $currentUser = $reflectionMethod->invoke($controller);

        $this->assertInstanceOf(CurrentUser::class, $currentUser);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testLoadAzureConfigurationLoadsConfigFileAndSkipsWhenConstantsAlreadyExist(): void
    {
        $azureConfigFile = PMF_CONFIG_DIR . '/azure.php';
        file_put_contents($azureConfigFile, <<<'PHP'
            <?php
            define('AAD_OAUTH_CLIENTID', 'test-client-id');
            PHP);

        $controller = new AzureAuthenticationController();
        $reflectionMethod = new \ReflectionMethod(AzureAuthenticationController::class, 'loadAzureConfiguration');

        $reflectionMethod->invoke($controller);
        $this->assertTrue(defined('AAD_OAUTH_CLIENTID'));
        $this->assertSame('test-client-id', AAD_OAUTH_CLIENTID);

        $reflectionMethod->invoke($controller);
        $this->assertTrue(defined('AAD_OAUTH_CLIENTID'));

        @unlink($azureConfigFile);
    }
}
