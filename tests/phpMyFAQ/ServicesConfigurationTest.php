<?php

declare(strict_types=1);

namespace phpMyFAQ;

use phpMyFAQ\Queue\Worker;
use phpMyFAQ\Scheduler\TaskScheduler;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

#[AllowMockObjectsWithoutExpectations]
class ServicesConfigurationTest extends TestCase
{
    use TestDatabaseTrait;

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

    /**
     * @return array<string, array{string, class-string}>
     */
    public static function commandLineEntryPointServiceProvider(): array
    {
        return [
            'bin/worker.php' => ['phpmyfaq.queue.worker', Worker::class],
            'bin/scheduler.php' => ['phpmyfaq.scheduler.task-scheduler', TaskScheduler::class],
        ];
    }

    /**
     * bin/worker.php and bin/scheduler.php compile a plain ContainerBuilder and fetch their
     * service from it. Compiling removes every private service, so these have to be public.
     *
     * @param class-string $expectedClass
     */
    #[DataProvider('commandLineEntryPointServiceProvider')]
    public function test_command_line_entry_point_service_survives_container_compilation(
        string $serviceId,
        string $expectedClass,
    ): void {
        $this->createTestConfiguration();

        $container = new ContainerBuilder();
        $loader = new PhpFileLoader($container, new FileLocator(dirname($this->servicesFile)));
        $loader->load(basename($this->servicesFile));
        $container->compile();

        $this->assertInstanceOf($expectedClass, $container->get($serviceId));
    }

    /**
     * PHP silently drops surplus constructor arguments, so a service wired with more arguments
     * than its constructor takes looks fine until someone relies on the dropped one.
     */
    public function test_no_service_gets_more_arguments_than_its_constructor_takes(): void
    {
        $container = new ContainerBuilder();
        $loader = new PhpFileLoader($container, new FileLocator(dirname($this->servicesFile)));
        $loader->load(basename($this->servicesFile));

        $surplus = [];
        foreach ($container->getDefinitions() as $serviceId => $definition) {
            $class = $definition->getClass();
            if ($definition->getFactory() !== null || $class === null || !class_exists($class)) {
                continue;
            }

            $constructor = new \ReflectionClass($class)->getConstructor();
            if ($constructor?->isVariadic() === true) {
                continue;
            }

            $accepted = $constructor?->getNumberOfParameters() ?? 0;
            $given = count($definition->getArguments());
            if ($given > $accepted) {
                $surplus[] = sprintf('%s (%s): %d given, %d accepted', $serviceId, $class, $given, $accepted);
            }
        }

        $this->assertSame([], $surplus);
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
        preg_match_all(
            '/\[\s*([\\\\A-Za-z0-9_]+)::class\s*,\s*[\'\"]([A-Za-z_][A-Za-z0-9_]*)[\'\"]\s*\]/',
            $contents,
            $factoryMatches,
        );
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
            if (
                !class_exists($fqcn)
                && !interface_exists($fqcn)
                && !(function_exists('enum_exists') && enum_exists($fqcn))
                && !trait_exists($fqcn)
            ) {
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
