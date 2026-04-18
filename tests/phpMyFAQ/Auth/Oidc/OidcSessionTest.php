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
        $session = new OidcSession(new Session(new MockArraySessionStorage()));
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
        $session = new OidcSession(new Session(new MockArraySessionStorage()));

        $session->setIdToken('id-token-123');
        $this->assertSame('id-token-123', $session->getIdToken());

        $session->clearIdToken();
        $this->assertSame('', $session->getIdToken());
    }
}
