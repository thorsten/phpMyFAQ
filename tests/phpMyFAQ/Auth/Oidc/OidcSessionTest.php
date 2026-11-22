<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\Oidc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[CoversClass(OidcSession::class)]
final class OidcSessionTest extends TestCase
{
    public function testSetAndClearAuthorizationState(): void
    {
        $session = new OidcSession(new Session(new MockArraySessionStorage()), 'keycloak');
        $session->setAuthorizationState('state-123', 'nonce-456', 'verifier-789');

        $this->assertSame(
            [
                'state' => 'state-123',
                'nonce' => 'nonce-456',
                'verifier' => 'verifier-789',
            ],
            $session->getAuthorizationState(),
        );

        $session->clearAuthorizationState();

        $this->assertSame(
            [
                'state' => '',
                'nonce' => '',
                'verifier' => '',
            ],
            $session->getAuthorizationState(),
        );
    }

    public function testSetAndClearIdToken(): void
    {
        $session = new OidcSession(new Session(new MockArraySessionStorage()), 'keycloak');

        $session->setIdToken('id-token-123');
        $this->assertSame('id-token-123', $session->getIdToken());

        $session->clearIdToken();
        $this->assertSame('', $session->getIdToken());
    }

    public function testProvidersDoNotShareState(): void
    {
        $symfonySession = new Session(new MockArraySessionStorage());
        $keycloak = new OidcSession($symfonySession, 'keycloak');
        $generic = new OidcSession($symfonySession, 'oidc');

        $keycloak->setAuthorizationState('kc-state', 'kc-nonce', 'kc-verifier');
        $keycloak->setIdToken('kc-token');

        $this->assertSame(['state' => '', 'nonce' => '', 'verifier' => ''], $generic->getAuthorizationState());
        $this->assertSame('', $generic->getIdToken());

        $generic->setAuthorizationState('g-state', 'g-nonce', 'g-verifier');
        $generic->clearIdToken();

        $this->assertSame(
            ['state' => 'kc-state', 'nonce' => 'kc-nonce', 'verifier' => 'kc-verifier'],
            $keycloak->getAuthorizationState(),
        );
        $this->assertSame('kc-token', $keycloak->getIdToken());
    }

    public function testRejectsEmptyProvider(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new OidcSession(new Session(new MockArraySessionStorage()), '');
    }

    public function testFallbackCookieCarriesTheStateAcrossALostSession(): void
    {
        $session = new OidcSession(new Session(new MockArraySessionStorage()), 'oidc');
        $cookie = $session->createFallbackCookie(
            'state-123',
            'nonce-456',
            'verifier-789',
            'https://faq.example.test/faq/',
        );

        $this->assertSame('pmf-oidc-oidc-authorization', $cookie->getName());
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());
        $this->assertNull($cookie->getDomain());
        $this->assertSame('/faq/', $cookie->getPath());
        $this->assertGreaterThan(time(), $cookie->getExpiresTime());
        $this->assertLessThanOrEqual(time() + 1800, $cookie->getExpiresTime());

        // The browser withheld the strict session cookie: a new, empty session answers the callback.
        $afterRedirect = new OidcSession(new Session(new MockArraySessionStorage()), 'oidc');
        $request = new Request(cookies: [$cookie->getName() => (string) $cookie->getValue()]);

        $this->assertSame(
            ['state' => 'state-123', 'nonce' => 'nonce-456', 'verifier' => 'verifier-789'],
            $afterRedirect->getAuthorizationState($request),
        );
        $this->assertSame('', $afterRedirect->getAuthorizationState()['state']);
    }

    public function testFallbackCookieUsesTheRootPathWithoutAUrlPath(): void
    {
        $session = new OidcSession(new Session(new MockArraySessionStorage()), 'oidc');

        $this->assertSame('/', $session->createFallbackCookie('s', 'n', 'v', 'https://faq.example.test')->getPath());
    }

    public function testStateInTheSessionWinsOverTheFallbackCookie(): void
    {
        $session = new OidcSession(new Session(new MockArraySessionStorage()), 'oidc');
        $cookie = $session->createFallbackCookie(
            'cookie-state',
            'cookie-nonce',
            'cookie-verifier',
            'https://faq.example.test/',
        );
        $session->setAuthorizationState('session-state', 'session-nonce', 'session-verifier');

        $this->assertSame(
            ['state' => 'session-state', 'nonce' => 'session-nonce', 'verifier' => 'session-verifier'],
            $session->getAuthorizationState(new Request(cookies: [$cookie->getName() => (string) $cookie->getValue()])),
        );
    }

    public function testFallbackCookieOfAnotherProviderIsIgnored(): void
    {
        $keycloak = new OidcSession(new Session(new MockArraySessionStorage()), 'keycloak');
        $cookie = $keycloak->createFallbackCookie('kc-state', 'kc-nonce', 'kc-verifier', 'https://faq.example.test/');

        $generic = new OidcSession(new Session(new MockArraySessionStorage()), 'oidc');
        $request = new Request(cookies: [$cookie->getName() => (string) $cookie->getValue()]);

        $this->assertSame('pmf-oidc-keycloak-authorization', $cookie->getName());
        $this->assertSame('', $generic->getAuthorizationState($request)['state']);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function unusableFallbackCookieProvider(): array
    {
        return [
            'empty' => [''],
            'not base64url' => ['***'],
            'not JSON' => [base64_encode('state-123')],
            'not an object' => [base64_encode('"state-123"')],
            'value missing' => [base64_encode('{"state":"s","nonce":"n"}')],
            'value of another type' => [base64_encode('{"state":["s"],"nonce":"n","verifier":"v"}')],
        ];
    }

    #[DataProvider('unusableFallbackCookieProvider')]
    public function testUnusableFallbackCookieYieldsNoState(string $value): void
    {
        $session = new OidcSession(new Session(new MockArraySessionStorage()), 'oidc');
        $request = new Request(cookies: ['pmf-oidc-oidc-authorization' => $value]);

        $this->assertSame(['state' => '', 'nonce' => '', 'verifier' => ''], $session->getAuthorizationState($request));
    }

    public function testExpireFallbackCookieRemovesItFromTheBrowser(): void
    {
        $session = new OidcSession(new Session(new MockArraySessionStorage()), 'oidc');
        $response = new Response();

        $session->expireFallbackCookie($response, 'https://faq.example.test/faq/');

        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertSame('pmf-oidc-oidc-authorization', $cookies[0]->getName());
        $this->assertSame('/faq/', $cookies[0]->getPath());
        $this->assertTrue($cookies[0]->isCleared());
    }
}
