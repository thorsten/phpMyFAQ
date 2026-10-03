<?php

declare(strict_types=1);

namespace phpMyFAQ\Configuration\Storage;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigurationStorageSettingsResolver::class)]
#[UsesClass(ConfigurationStorageSettings::class)]
final class ConfigurationStorageSettingsResolverTest extends TestCase
{
    /**
     * @param array<string, ?string> $values
     */
    private function createResolver(array $values): ConfigurationStorageSettingsResolver
    {
        $store = $this->createStub(DatabaseConfigurationStore::class);
        $store->method('fetchValue')->willReturnCallback(static fn(string $name): ?string => $values[$name] ?? null);

        return new ConfigurationStorageSettingsResolver($store);
    }

    public function testResolveFallsBackToDefaultsWhenNothingIsConfigured(): void
    {
        $settings = $this->createResolver([])->resolve();

        $this->assertFalse($settings->enabled);
        $this->assertSame('tcp://redis:6379?database=1', $settings->redisDsn);
        $this->assertSame('pmf:config:', $settings->redisPrefix);
        $this->assertSame(1.0, $settings->connectTimeout);
    }

    public function testResolveUsesConfiguredValues(): void
    {
        $settings = $this->createResolver([
            'storage.useRedisForConfiguration' => 'true',
            'storage.redisDsn' => '  tcp://cache.internal:6380?database=3  ',
            'storage.redisPrefix' => 'tenant-a:',
            'storage.redisConnectTimeout' => '2.5',
        ])->resolve();

        $this->assertTrue($settings->enabled);
        $this->assertSame('tcp://cache.internal:6380?database=3', $settings->redisDsn);
        $this->assertSame('tenant-a:', $settings->redisPrefix);
        $this->assertSame(2.5, $settings->connectTimeout);
    }

    /**
     * @return iterable<string, array{?string, bool}>
     */
    public static function enabledValueProvider(): iterable
    {
        yield 'true' => ['true', true];
        yield 'TRUE' => ['TRUE', true];
        yield 'yes' => ['yes', true];
        yield 'on' => ['On', true];
        yield '1' => ['1', true];
        yield 'false' => ['false', false];
        yield '0' => ['0', false];
        yield 'no' => ['no', false];
        yield 'empty' => ['', false];
        yield 'garbage' => ['enabled', false];
        yield 'not set' => [null, false];
    }

    #[DataProvider('enabledValueProvider')]
    public function testResolveParsesEnabledFlag(?string $value, bool $expected): void
    {
        $settings = $this->createResolver(['storage.useRedisForConfiguration' => $value])->resolve();

        $this->assertSame($expected, $settings->enabled);
    }

    public function testResolveFallsBackToDefaultDsnForBlankValue(): void
    {
        $settings = $this->createResolver(['storage.redisDsn' => '   '])->resolve();

        $this->assertSame('tcp://redis:6379?database=1', $settings->redisDsn);
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function invalidTimeoutProvider(): iterable
    {
        yield 'zero' => ['0'];
        yield 'negative' => ['-1'];
        yield 'empty' => [''];
        yield 'text' => ['fast'];
        yield 'not set' => [null];
    }

    #[DataProvider('invalidTimeoutProvider')]
    public function testResolveFallsBackToDefaultTimeoutForNonPositiveValues(?string $value): void
    {
        $settings = $this->createResolver(['storage.redisConnectTimeout' => $value])->resolve();

        $this->assertSame(1.0, $settings->connectTimeout);
    }
}
