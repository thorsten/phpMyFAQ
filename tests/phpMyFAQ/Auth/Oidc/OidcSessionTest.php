<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\Oidc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
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
}
