<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Auth\OAuth2\AuthorizationServer as OAuth2AuthorizationServer;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
class OAuth2ControllerTest extends TestCase
{
    private Configuration $configuration;

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

        $this->configuration = Configuration::getConfigurationInstance();
    }

    /**
     * @throws \Exception
     */
    public function testTokenReturnsServiceUnavailableWhenOAuth2NotConfigured(): void
    {
        $controller = new OAuth2Controller();
        $response = $controller->token(new Request([], [], [], [], [], [], ''));

        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $this->assertStringContainsString('oauth2_unavailable', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testTokenReturnsIssuerResponsePayload(): void
    {
        $controller = new OAuth2Controller();
        $authorizationServer = new OAuth2AuthorizationServer($this->configuration);
        $authorizationServer->setTokenIssuer(static fn(): array => [
            'body' => ['access_token' => 'abc123', 'token_type' => 'Bearer'],
            'status' => Response::HTTP_OK,
            'headers' => ['Cache-Control' => 'no-store'],
        ]);
        $controller->setAuthorizationServer($authorizationServer);

        $response = $controller->token(new Request([], [], [], [], [], [], ''));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('abc123', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testAuthorizeRequiresAuthenticatedUser(): void
    {
        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(false);

        $controller = new OAuth2Controller();
        $reflection = new \ReflectionProperty($controller, 'currentUser');
        $reflection->setValue($controller, $currentUser);

        $response = $controller->authorize(new Request());

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertStringContainsString('access_denied', (string) $response->getContent());
    }
}
