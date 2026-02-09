<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class ResourceServerTest extends TestCase
{
    public function testAuthenticateReturnsNullWithoutBearerHeader(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $server = new ResourceServer($configuration);

        $this->assertNull($server->authenticate(new Request()));
    }

    public function testAuthenticateUsesConfiguredValidator(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $server = new ResourceServer($configuration);
        $server->setTokenValidator(static fn(Request $request): ?int => 123);

        $request = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer oauth-access-token']);
        $this->assertSame(123, $server->authenticate($request));
    }
}
