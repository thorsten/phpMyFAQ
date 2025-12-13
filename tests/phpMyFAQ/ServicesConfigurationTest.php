<?php

declare(strict_types=1);

namespace phpMyFAQ;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class ServicesConfigurationTest extends TestCase
{
    /** @var string */
    private string $servicesFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicesFile = dirname(__DIR__, 2) . '/phpmyfaq/src/services.php';

        $composerAutoload = dirname(__DIR__, 2) . '/phpmyfaq/src/libs/autoload.php';
        if (is_file($composerAutoload)) {
            require_once $composerAutoload;
        }
    }

    public function test_service_configuration_classes_exist_and_factories_are_callable(): void
    {
        $contents = file_get_contents($this->servicesFile);
        $this->assertIsString($contents, 'services.php could not be read');

        // Build alias map from use statements
        preg_match_all('/^use\s+([^;]+);/mi', $contents, $useMatches);
        $aliases = [];
        foreach ($useMatches[1] ?? [] as $useLine) {
            $useLine = trim($useLine);
            if (preg_match('/^([A-Za-z0-9_\\\\]+)\s+as\s+([A-Za-z0-9_]+)$/', $useLine, $m)) {
                $aliases[$m[2]] = ltrim($m[1], '\\');
            } else {
                $fq = ltrim($useLine, '\\');
                $parts = explode('\\', $fq);
                $alias = end($parts);
                if ($alias !== false && $alias !== '') {
                    $aliases[$alias] = $fq;
                }
            }
        }

        // Find all Foo\Bar::class occurrences
        preg_match_all('/([\\\\A-Za-z0-9_]+)::class/', $contents, $classConstMatches);
        $fqSymbols = [];
        foreach ($classConstMatches[1] ?? [] as $raw) {
            $fqSymbols[] = $this->resolveToFqcn($raw, $aliases);
        }

        // Find all factory arrays like [Foo\Bar::class, 'method']
        preg_match_all('/\[\s*([\\\\A-Za-z0-9_]+)::class\s*,\s*[\'\"]([A-Za-z_][A-Za-z0-9_]*)[\'\"]\s*\]/', $contents, $factoryMatches);
        $factories = [];
        $count = count($factoryMatches[1] ?? []);
        for ($i = 0; $i < $count; $i++) {
            $class = $this->resolveToFqcn($factoryMatches[1][$i], $aliases);
            $method = $factoryMatches[2][$i] ?? null;
            if ($method) {
                $factories[] = [$class, $method];
            }
        }

        $fqSymbols = array_values(array_unique($fqSymbols));

        $missing = [];
        foreach ($fqSymbols as $fqcn) {
            if (!class_exists($fqcn) && !interface_exists($fqcn) && !(function_exists('enum_exists') && enum_exists($fqcn)) && !trait_exists($fqcn)) {
                $missing[] = $fqcn;
            }
        }
        $this->assertEmpty($missing, 'Missing classes in services.php: ' . implode(', ', $missing));

        $badFactories = [];
        foreach ($factories as [$class, $method]) {
            if (!method_exists($class, $method)) {
                $badFactories[] = $class . '::' . $method . ' (method missing)';
            }
        }
        $this->assertEmpty($badFactories, 'Invalid factory methods in services.php: ' . implode(', ', $badFactories));
    }

    private function resolveToFqcn(string $raw, array $aliases): string
    {
        if (str_starts_with($raw, '\\')) {
            return ltrim($raw, '\\');
        }

        $parts = explode('\\', $raw);
        $first = $parts[0] ?? '';
        if (isset($aliases[$first])) {
            $parts[0] = $aliases[$first];
            return implode('\\', $parts);
        }

        return $raw;
    }
}
