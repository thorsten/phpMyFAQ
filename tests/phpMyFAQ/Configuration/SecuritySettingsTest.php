<?php

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use phpMyFAQ\Configuration as CoreConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SecuritySettings::class)]
final class SecuritySettingsTest extends TestCase
{
    /**
     * @param array<string, mixed> $values
     */
    private function createSettings(array $values): SecuritySettings
    {
        $configuration = $this->createStub(CoreConfiguration::class);
        $configuration->method('get')->willReturnCallback(static fn(string $item): mixed => $values[$item] ?? null);

        return new SecuritySettings($configuration);
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function flagProvider(): iterable
    {
        yield 'bool true' => [true, true];
        yield 'bool false' => [false, false];
        yield 'string 1' => ['1', true];
        yield 'string 0' => ['0', false];
        yield 'int 1' => [1, true];
        yield 'not configured' => [null, false];
    }

    #[DataProvider('flagProvider')]
    public function testIsSignInWithMicrosoftActive(mixed $configured, bool $expected): void
    {
        $settings = $this->createSettings(['security.enableSignInWithMicrosoft' => $configured]);

        $this->assertSame($expected, $settings->isSignInWithMicrosoftActive());
    }

    #[DataProvider('flagProvider')]
    public function testIsSignInWithKeycloakActive(mixed $configured, bool $expected): void
    {
        $settings = $this->createSettings(['keycloak.enable' => $configured]);

        $this->assertSame($expected, $settings->isSignInWithKeycloakActive());
    }

    public function testFlagsAreIndependentOfEachOther(): void
    {
        $settings = $this->createSettings(['security.enableSignInWithMicrosoft' => true, 'keycloak.enable' => false]);

        $this->assertTrue($settings->isSignInWithMicrosoftActive());
        $this->assertFalse($settings->isSignInWithKeycloakActive());
    }
}
