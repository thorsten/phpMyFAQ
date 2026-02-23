<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use Exception;
use phpMyFAQ\Auth\AuthEntraId;
use phpMyFAQ\Auth\EntraId\EntraIdSession;
use phpMyFAQ\Auth\EntraId\OAuth;
use phpMyFAQ\Core\Exception as CoreException;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
class AzureAuthenticationControllerTest extends TestCase
{
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
}
