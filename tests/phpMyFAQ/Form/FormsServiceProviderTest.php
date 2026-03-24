<?php

declare(strict_types=1);

namespace phpMyFAQ\Form;

use phpMyFAQ\Forms;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

#[CoversClass(FormsServiceProvider::class)]
final class FormsServiceProviderTest extends TestCase
{
    public function testRegisterAddsRepositoryAliasAndFormsDefinitions(): void
    {
        $container = new ContainerBuilder();
        $container->register('phpmyfaq.configuration');

        FormsServiceProvider::register($container);

        $this->assertTrue($container->hasDefinition(FormsRepository::class));
        $this->assertTrue($container->hasAlias(FormsRepositoryInterface::class));
        $this->assertTrue($container->hasDefinition(Forms::class));

        $repositoryDefinition = $container->getDefinition(FormsRepository::class);
        $this->assertSame(FormsRepository::class, $repositoryDefinition->getClass());
        $this->assertTrue($repositoryDefinition->isPublic());
        $this->assertEquals(new Reference('phpmyfaq.configuration'), $repositoryDefinition->getArgument(0));

        $repositoryAlias = $container->getAlias(FormsRepositoryInterface::class);
        $this->assertInstanceOf(Alias::class, $repositoryAlias);
        $this->assertSame(FormsRepository::class, (string) $repositoryAlias);
        $this->assertTrue($repositoryAlias->isPublic());

        $formsDefinition = $container->getDefinition(Forms::class);
        $this->assertSame(Forms::class, $formsDefinition->getClass());
        $this->assertTrue($formsDefinition->isPublic());
        $this->assertEquals(new Reference('phpmyfaq.configuration'), $formsDefinition->getArgument(0));
        $this->assertEquals(new Reference(FormsRepositoryInterface::class), $formsDefinition->getArgument(1));
    }

    public function testRegisterKeepsExistingDefinitionsAndAliasUntouched(): void
    {
        $container = new ContainerBuilder();
        $container->register('phpmyfaq.configuration');

        $existingRepositoryDefinition = new Definition(\stdClass::class);
        $existingFormsDefinition = new Definition(\ArrayObject::class);
        $existingAlias = new Alias('custom.forms.repository', false);

        $container->setDefinition(FormsRepository::class, $existingRepositoryDefinition);
        $container->setAlias(FormsRepositoryInterface::class, $existingAlias);
        $container->setDefinition(Forms::class, $existingFormsDefinition);

        FormsServiceProvider::register($container);

        $this->assertSame($existingRepositoryDefinition, $container->getDefinition(FormsRepository::class));
        $this->assertSame($existingFormsDefinition, $container->getDefinition(Forms::class));
        $this->assertSame('custom.forms.repository', (string) $container->getAlias(FormsRepositoryInterface::class));
        $this->assertFalse($container->getAlias(FormsRepositoryInterface::class)->isPublic());
    }
}
