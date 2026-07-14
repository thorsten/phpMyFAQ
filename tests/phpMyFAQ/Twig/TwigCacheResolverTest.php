<?php

namespace phpMyFAQ\Twig;

use PHPUnit\Framework\TestCase;

class TwigCacheResolverTest extends TestCase
{
    public function testReturnsDefaultDirWhenEnabledInProduction(): void
    {
        $result = TwigCacheResolver::resolve(
            debug: false,
            enabled: 'true',
            configuredDir: null,
            defaultDir: '/var/www/cache/twig',
        );

        $this->assertSame('/var/www/cache/twig', $result);
    }

    public function testReturnsConfiguredDirWhenSet(): void
    {
        $result = TwigCacheResolver::resolve(
            debug: false,
            enabled: 'true',
            configuredDir: '/custom/twig-cache',
            defaultDir: '/var/www/cache/twig',
        );

        $this->assertSame('/custom/twig-cache', $result);
    }

    public function testReturnsFalseInDebugMode(): void
    {
        $result = TwigCacheResolver::resolve(
            debug: true,
            enabled: 'true',
            configuredDir: null,
            defaultDir: '/var/www/cache/twig',
        );

        $this->assertFalse($result);
    }

    public function testReturnsFalseWhenDisabledByEnvironment(): void
    {
        $result = TwigCacheResolver::resolve(
            debug: false,
            enabled: 'false',
            configuredDir: null,
            defaultDir: '/var/www/cache/twig',
        );

        $this->assertFalse($result);
    }

    public function testIsEnabledByDefault(): void
    {
        $result = TwigCacheResolver::resolve(
            debug: false,
            enabled: null,
            configuredDir: null,
            defaultDir: '/var/www/cache/twig',
        );

        $this->assertSame('/var/www/cache/twig', $result);
    }

    public function testIgnoresBlankConfiguredDir(): void
    {
        $result = TwigCacheResolver::resolve(
            debug: false,
            enabled: 'true',
            configuredDir: '   ',
            defaultDir: '/var/www/cache/twig',
        );

        $this->assertSame('/var/www/cache/twig', $result);
    }
}
