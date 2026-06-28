<?php

namespace phpMyFAQ\Enums;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthenticationSourceType::class)]
class AuthenticationSourceTypeTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame('local', AuthenticationSourceType::AUTH_LOCAL->value);
        $this->assertSame('azure', AuthenticationSourceType::AUTH_AZURE->value);
        $this->assertSame('keycloak', AuthenticationSourceType::AUTH_KEYCLOAK->value);
        $this->assertSame('ldap', AuthenticationSourceType::AUTH_LDAP->value);
        $this->assertSame('http', AuthenticationSourceType::AUTH_HTTP->value);
        $this->assertSame('sso', AuthenticationSourceType::AUTH_SSO->value);
        $this->assertSame('webauthn', AuthenticationSourceType::AUTH_WEB_AUTHN->value);
    }

    public function testCasesAreComplete(): void
    {
        $cases = AuthenticationSourceType::cases();

        $this->assertCount(7, $cases);
        $this->assertContains(AuthenticationSourceType::AUTH_LOCAL, $cases);
        $this->assertContains(AuthenticationSourceType::AUTH_AZURE, $cases);
        $this->assertContains(AuthenticationSourceType::AUTH_KEYCLOAK, $cases);
        $this->assertContains(AuthenticationSourceType::AUTH_LDAP, $cases);
        $this->assertContains(AuthenticationSourceType::AUTH_HTTP, $cases);
        $this->assertContains(AuthenticationSourceType::AUTH_SSO, $cases);
        $this->assertContains(AuthenticationSourceType::AUTH_WEB_AUTHN, $cases);
    }

    public function testTryFromValidValues(): void
    {
        foreach (AuthenticationSourceType::cases() as $case) {
            $this->assertSame($case, AuthenticationSourceType::tryFrom($case->value));
        }
    }

    public function testTryFromInvalidValueReturnsNull(): void
    {
        $this->assertNull(AuthenticationSourceType::tryFrom('definitely-invalid'));
    }

    public function testFromInvalidValueThrows(): void
    {
        $this->expectException(\ValueError::class);

        AuthenticationSourceType::from('definitely-invalid');
    }

    public function testValuesAreUnique(): void
    {
        $values = array_map(
            static fn(AuthenticationSourceType $case): string => $case->value,
            AuthenticationSourceType::cases(),
        );

        $this->assertCount(count($values), array_unique($values));
    }
}
