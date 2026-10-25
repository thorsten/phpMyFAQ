<?php

declare(strict_types=1);

namespace phpMyFAQ\Bootstrap;

use LogicException;
use phpMyFAQ\Configuration;
use phpMyFAQ\Container\ContainerRegistry;
use phpMyFAQ\Database;
use phpMyFAQ\Database\PdoSqlite;
use phpMyFAQ\Instance\Database as InstanceDatabase;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\TwigWrapper;
use phpMyFAQ\User\Tracking;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(WorkerState::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ContainerRegistry::class)]
#[UsesClass(Database::class)]
#[UsesClass(PdoSqlite::class)]
#[UsesClass(InstanceDatabase::class)]
#[UsesClass(Token::class)]
#[UsesClass(Strings::class)]
#[UsesClass(Translation::class)]
#[UsesClass(TwigWrapper::class)]
#[UsesClass(Tracking::class)]
final class WorkerStateTest extends TestCase
{
    /** @var array<string, array{ReflectionProperty, mixed}> */
    private array $previous = [];

    protected function setUp(): void
    {
        foreach (
            [
                [Configuration::class, 'configuration'],
                [Database::class, 'databaseDriver'],
                [Database::class, 'dbType'],
                [Database::class, 'tablePrefix'],
                [Translation::class, 'translation'],
                [Strings::class, 'instance'],
                [Token::class, 'token'],
                [Tracking::class, 'tracking'],
                [ContainerRegistry::class, 'container'],
                [TwigWrapper::class, 'templateSetName'],
                [InstanceDatabase::class, 'driver'],
            ] as [$class, $property]
        ) {
            $reflection = new ReflectionProperty($class, $property);
            $this->previous[$class . '::' . $property] = [$reflection, $reflection->getValue()];
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->previous as [$reflection, $value]) {
            $reflection->setValue(null, $value);
        }
    }

    public function testResetForgetsTheConfigurationSingleton(): void
    {
        new ReflectionProperty(Configuration::class, 'configuration')->setValue(
            null,
            $this->createStub(Configuration::class),
        );

        WorkerState::reset();

        $this->expectException(LogicException::class);
        Configuration::getConfigurationInstance();
    }

    public function testResetForgetsTheDatabaseConnectionAndTablePrefix(): void
    {
        Database::factory('pdo_sqlite');
        Database::setTablePrefix('pmf_');

        WorkerState::reset();

        $this->assertSame('', Database::getType());
        $this->assertSame('', Database::getTablePrefix());
        $this->assertNull(new ReflectionProperty(Database::class, 'databaseDriver')->getValue());
    }

    public function testResetForgetsTheRemainingRequestScopedSingletons(): void
    {
        $translation = Translation::create();
        Strings::init();
        ContainerRegistry::set($this->createStub(ContainerInterface::class));
        TwigWrapper::setTemplateSetName('tenant-theme');
        new ReflectionProperty(Token::class, 'token')->setValue(null, $this->createStub(Token::class));
        new ReflectionProperty(Tracking::class, 'tracking')->setValue(null, $this->createStub(Tracking::class));
        new ReflectionProperty(InstanceDatabase::class, 'driver')->setValue(
            null,
            $this->createStub(\phpMyFAQ\Instance\Database\DriverInterface::class),
        );

        WorkerState::reset();

        $this->assertNotSame($translation, Translation::create());
        $this->assertNull(new ReflectionProperty(Strings::class, 'instance')->getValue());
        $this->assertNull(ContainerRegistry::get());
        $this->assertSame('default', TwigWrapper::getTemplateSetName());
        $this->assertNull(new ReflectionProperty(Token::class, 'token')->getValue());
        $this->assertNull(new ReflectionProperty(Tracking::class, 'tracking')->getValue());
        $this->assertNull(InstanceDatabase::getInstance());
    }
}
